<?php

namespace App\Entity;

use App\Repository\PollAnswerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PollAnswerRepository::class)]
class PollAnswer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'pollAnswers')]
    private ?Poll $poll = null;

    #[ORM\ManyToOne(inversedBy: 'pollAnswers')]
    private ?PollChoice $choice = null;

    #[ORM\ManyToOne(inversedBy: 'pollAnswers')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoll(): ?Poll
    {
        return $this->poll;
    }

    public function setPoll(?Poll $poll): static
    {
        $this->poll = $poll;

        return $this;
    }

    public function getChoice(): ?PollChoice
    {
        return $this->choice;
    }

    public function setChoice(?PollChoice $choice): static
    {
        $this->choice = $choice;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
