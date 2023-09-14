<?php

namespace App\Entity;

use App\Repository\PollRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PollRepository::class)]
class Poll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'poll', targetEntity: PollChoice::class)]
    private Collection $choice;

    #[ORM\Column(length: 255)]
    private ?string $question = null;

    #[ORM\Column(nullable: true)]
    private ?bool $closed = null;

    #[ORM\OneToMany(mappedBy: 'poll', targetEntity: PollAnswer::class)]
    private Collection $pollAnswers;

    public function __construct()
    {
        $this->choice = new ArrayCollection();
        $this->pollAnswers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, PollChoice>
     */
    public function getChoice(): Collection
    {
        return $this->choice;
    }

    public function addChoice(PollChoice $choice): static
    {
        if (!$this->choice->contains($choice)) {
            $this->choice->add($choice);
            $choice->setPoll($this);
        }

        return $this;
    }

    public function removeChoice(PollChoice $choice): static
    {
        if ($this->choice->removeElement($choice)) {
            // set the owning side to null (unless already changed)
            if ($choice->getPoll() === $this) {
                $choice->setPoll(null);
            }
        }

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function isClosed(): ?bool
    {
        return $this->closed;
    }

    public function setClosed(?bool $closed): static
    {
        $this->closed = $closed;

        return $this;
    }

    /**
     * @return Collection<int, PollAnswer>
     */
    public function getPollAnswers(): Collection
    {
        return $this->pollAnswers;
    }

    public function addPollAnswer(PollAnswer $pollAnswer): static
    {
        if (!$this->pollAnswers->contains($pollAnswer)) {
            $this->pollAnswers->add($pollAnswer);
            $pollAnswer->setPoll($this);
        }

        return $this;
    }

    public function removePollAnswer(PollAnswer $pollAnswer): static
    {
        if ($this->pollAnswers->removeElement($pollAnswer)) {
            // set the owning side to null (unless already changed)
            if ($pollAnswer->getPoll() === $this) {
                $pollAnswer->setPoll(null);
            }
        }

        return $this;
    }
}
