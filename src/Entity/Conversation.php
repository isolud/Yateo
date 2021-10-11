<?php

namespace App\Entity;

use App\Repository\ConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ConversationRepository::class)
 */
class Conversation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="conversations")
     */
    private $recipients;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subject;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="conversations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $author;

    /**
     * @ORM\OneToOne(targetEntity=PrivateMessage::class, inversedBy="conversation", cascade={"persist", "remove"})
     */
    private $firstMessage;

    /**
     * @ORM\OneToOne(targetEntity=PrivateMessage::class, cascade={"persist", "remove"})
     */
    private $lastMessage;

    /**
     * @ORM\OneToMany(targetEntity=PrivateMessage::class, mappedBy="conversation", orphanRemoval=true)
     */
    private $messages;

    /**
     * @ORM\OneToMany(targetEntity=PrivateMessage::class, mappedBy="Conversation", orphanRemoval=true)
     */
    private $privateMessages;

    /**
     * @ORM\OneToMany(targetEntity=ConversationMessageRead::class, mappedBy="conversation", orphanRemoval=true)
     */
    private $conversationMessageReads;

    public function __construct()
    {
        $this->recipients = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->privateMessages = new ArrayCollection();
        $this->created    = new \DateTime();
        $this->conversationMessageReads = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|User[]
     */
    public function getRecipients(): Collection
    {
        return $this->recipients;
    }

    public function addRecipient(User $recipient): self
    {
        if (!$this->recipients->contains($recipient)) {
            $this->recipients[] = $recipient;
        }

        return $this;
    }

    public function removeRecipient(User $recipient): self
    {
        if ($this->recipients->contains($recipient)) {
            $this->recipients->removeElement($recipient);
        }

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getFirstMessage(): ?PrivateMessage
    {
        return $this->firstMessage;
    }

    public function setFirstMessage(?PrivateMessage $firstMessage): self
    {
        $this->firstMessage = $firstMessage;

        return $this;
    }

    public function getLastMessage(): ?PrivateMessage
    {
        return $this->lastMessage;
    }

    public function setLastMessage(?PrivateMessage $lastMessage): self
    {
        $this->lastMessage = $lastMessage;

        return $this;
    }

    /**
     * @return Collection|PrivateMessage[]
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(PrivateMessage $message): self
    {
        if (!$this->messages->contains($message)) {
            $this->messages[] = $message;
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(PrivateMessage $message): self
    {
        if ($this->messages->contains($message)) {
            $this->messages->removeElement($message);
            // set the owning side to null (unless already changed)
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|PrivateMessage[]
     */
    public function getPrivateMessages(): Collection
    {
        return $this->privateMessages;
    }

    public function addPrivateMessage(PrivateMessage $privateMessage): self
    {
        if (!$this->privateMessages->contains($privateMessage)) {
            $this->privateMessages[] = $privateMessage;
            $privateMessage->setConversation($this);
        }

        return $this;
    }

    public function removePrivateMessage(PrivateMessage $privateMessage): self
    {
        if ($this->privateMessages->contains($privateMessage)) {
            $this->privateMessages->removeElement($privateMessage);
            // set the owning side to null (unless already changed)
            if ($privateMessage->getConversation() === $this) {
                $privateMessage->setConversation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|ConversationMessageRead[]
     */
    public function getConversationMessageReads(): Collection
    {
        return $this->conversationMessageReads;
    }

    public function addConversationMessageRead(ConversationMessageRead $conversationMessageRead): self
    {
        if (!$this->conversationMessageReads->contains($conversationMessageRead)) {
            $this->conversationMessageReads[] = $conversationMessageRead;
            $conversationMessageRead->setConversation($this);
        }

        return $this;
    }

    public function removeConversationMessageRead(ConversationMessageRead $conversationMessageRead): self
    {
        if ($this->conversationMessageReads->contains($conversationMessageRead)) {
            $this->conversationMessageReads->removeElement($conversationMessageRead);
            // set the owning side to null (unless already changed)
            if ($conversationMessageRead->getConversation() === $this) {
                $conversationMessageRead->setConversation(null);
            }
        }

        return $this;
    }
}
