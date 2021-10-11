<?php

namespace App\Controller;


use App\Entity\Conversation;
use App\Entity\ConversationMessageRead;
use App\Entity\PrivateMessage;
use App\Entity\PrivateMessageAttachment;
use App\Entity\User;
use App\Form\ConversationType;
use App\Form\PrivateMessageType;
use App\Repository\ConversationMessageReadRepository;
use App\Repository\ConversationRepository;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use WhiteOctober\BreadcrumbsBundle\Model\Breadcrumbs;

/**
 * @Route("/messages")
 */
class ConversationController extends AbstractController {


    const CONVERSATION_PER_PAGE = 5;
    private $conversationMessageReadRepository;

    public function __construct(ConversationMessageReadRepository $conversationMessageReadRepository)
    {
        $this->conversationMessageReadRepository = $conversationMessageReadRepository;
    }

    /**
     * @Route("/list", name="messages_list", methods={"GET"})
     */
    public function listAction(ConversationRepository $conversationRepository, Request $request,
                               PaginatorInterface $paginator, BreadCrumbs $breadcrumbs): Response
    {

        $breadcrumbs->addItem("Dashboard", $this->get("router")->generate("app_index"));
        $breadcrumbs->addItem("Messagerie", $this->get("router")->generate("messages_list"));

        // Get all conversations of current user.
        $query = $conversationRepository
            ->getAllByRecipient($this->getUser());

        $conversations = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            self::CONVERSATION_PER_PAGE
        );

        $conversations->setUsedRoute('ajax_messages_list');

        $formView = null;
        $conversation = null;

        if(isset($conversations[0]))
        {

            $conversation = $conversationRepository->getOneById($conversations[0]->getId());

            $this->setLastMessageRead($conversation);

            $privateMessage = new PrivateMessage();
            $privateMessage
                ->setConversation($conversation)
                ->setAuthor($this->getUser());

            $form = $this->createForm(PrivateMessageType::class, $privateMessage, [
                'action' => $this->generateUrl('messages_show', ['conversation' => $conversation->getId()])
            ]);
            $formView = $form->createView();


        }


        return $this->render('conversation/index.html.twig', array(
            'user' => $this->getUser(),
            'conversations' => $conversations,
            'conversation' => $conversation,
            'form' => $formView
        ));
    }

    /**
     * @Route("/ajax-list", name="ajax_messages_list", methods={"GET"})
     */
    public function ajaxListAction(ConversationRepository $conversationRepository, Request $request,
                               PaginatorInterface $paginator): JsonResponse
    {
        // Get all conversations of current user.
        $query = $conversationRepository
            ->getAllByRecipient($this->getUser());

        $conversations = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            self::CONVERSATION_PER_PAGE
        );

        $conversations->setUsedRoute('ajax_messages_list');

        $json = array();

        $json['list'] = $this->renderView('conversation/_list.html.twig', array(
            'user' => $this->getUser(),
            'conversations' => $conversations,
            'ajax' => true

        ));

        $json['pagination'] = $this->renderView('conversation/_pagination.html.twig', array(
            'conversations' => $conversations
        ));

        return $this->json($json);
    }

    /**
     * @Route("/show/{conversation}", name="messages_show", methods={"GET","POST"})
     * @ParamConverter(name="Conversation", class="App:Conversation", options={
     *     "repository_method" = "getOneById()",
     *     "expr" = "repository.getOneById(conversation)",
     *     "map_method_signature" = true
     * })
     * @IsGranted("POST_VIEW",subject="conversation")
     */
    public function showAction(Conversation $conversation, Request $request): Response
    {

        //Set last message read for user
        $this->setLastMessageRead($conversation);

        // Create the answer form.
        $privateMessage = new PrivateMessage();
        $privateMessage
            ->setConversation($conversation)
            ->setAuthor($this->getUser());

        $form = $this->createForm(PrivateMessageType::class, $privateMessage, [
            'action' => $this->generateUrl('messages_show', ['conversation' => $conversation->getId()])
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($privateMessage);

            /* Private message attachment */
            $attachments = $request->request->get('attachment');
            if($attachments)
            {
                foreach($attachments as $attachment)
                {
                    $privateMessageAttachment = new PrivateMessageAttachment();
                    $privateMessageAttachment->setFilename($attachment);
                    $privateMessageAttachment->setPrivateMessage($privateMessage);
                    $em->persist($privateMessageAttachment);
                }

            }



            $em->flush();

            return $this->redirect($this->generateUrl('messages_show', ['conversation' => $conversation->getId()]));
        }

        return $this->render('conversation/_show.html.twig', array(
            'user' => $this->getUser(),
            'conversation' => $conversation,
            'form'         => $form->createView(),
        ));
    }

    /**
     * @Route("/new", name="messages_new", methods={"GET","POST"})
     */
    public function createAction(Request $request, TranslatorInterface $translator, MailerInterface $mailer)
    {
        $conversation = $this->buildConversation();
        $toUserId = $request->query->get('user', null);

        $form = $this->createForm(ConversationType::class, $conversation);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $recipients = $form->get('recipients')->getData();
            $messagesLink = $this->get("router")->generate("messages_list", [], urlGeneratorInterface::ABSOLUTE_URL);
            $message = $form->get('firstMessage')->get('body')->getData();



            //Send email if new message if user asks for it
            foreach($recipients as $recipient)
            {
                if($recipient->getSetting())
                {
                    if($recipient->getSetting()->getSendEmailNewMessage())
                    {
                        $userEmail = $recipient->getEmail();
                        $email = (new TemplatedEmail())
                            ->from('redacted@isolud.com')
                            ->to(new Address(trim($userEmail)))
                            ->subject('Vous avez reçu un nouveau message')
                            ->htmlTemplate('conversation/receive_message.html.twig')
                            ->context([

                                'recipient' => $recipient,
                                'sender' => $this->getUser(),
                                'messagesLink' => $messagesLink
                            ])
                        ;
                        $mailer->send($email);
                    }
                }
            }
            // Add the current user as recipient anyway.
            $conversation->addRecipient($this->getUser());

            $em = $this->getDoctrine()->getManager();
            $em->persist($conversation);


            /* Private message attachment */
            $attachments = $request->request->get('attachment');
            if($attachments)
            {
                foreach($attachments as $attachment)
                {
                    $privateMessageAttachment = new PrivateMessageAttachment();
                    $privateMessageAttachment->setFilename($attachment);
                    $privateMessageAttachment->setPrivateMessage($conversation->getFirstMessage());
                    $em->persist($privateMessageAttachment);
                }

            }


            $em->flush();

            $this->setLastMessageRead($conversation);

            $this->addFlash('success', $translator->trans('Conversation created'));

            return $this->redirect($this->generateUrl('messages_list'));
        }

        return $this->render('conversation/create.html.twig', array(
            'user' => $this->getUser(),
            'form' => $form->createView(),
            'toUserId' => $toUserId
        ));
    }


    protected function buildConversation()
    {
        $user = $this->getUser();
        $conversation = new Conversation();

        $message = new PrivateMessage();

        $message
            ->setAuthor($user)
            ->setConversation($conversation);

        $conversation
            ->setAuthor($user)
            ->setFirstMessage($message)
            ->setLastMessage($message);

        return $conversation;
    }

    protected function setLastMessageRead($conversation)
    {
        //Set last message read for user
        $conversationMessageRead = $this->conversationMessageReadRepository->findOneBy([
            'user'=>$this->getUser(),
            'conversation'=>$conversation
        ]);



        if(!$conversationMessageRead)
        {
            $conversationMessageRead = new conversationMessageRead();
            $conversationMessageRead->setUser($this->getUser());
            $conversationMessageRead->setConversation($conversation);
        }

        $lastMessage = $conversation->getFirstMessage();
        $conversationMessageRead->setLastMessageRead($lastMessage);

        $em = $this->getDoctrine()->getManager();
        $em->persist($conversationMessageRead);
        $em->flush();
    }

} 