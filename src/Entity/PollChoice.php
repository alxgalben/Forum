<?php

namespace App\Entity;

use App\Repository\PollChoiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PollChoiceRepository::class)]
class PollChoice
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'choice')]
    private ?Poll $poll = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $content = null;

    #[ORM\OneToMany(mappedBy: 'choice', targetEntity: PollAnswer::class)]
    private Collection $pollAnswers;

    public function __construct()
    {
        $this->pollAnswers = new ArrayCollection();
    }

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

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        $this->content = $content;

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
            $pollAnswer->setChoice($this);
        }

        return $this;
    }

    public function removePollAnswer(PollAnswer $pollAnswer): static
    {
        if ($this->pollAnswers->removeElement($pollAnswer)) {
            // set the owning side to null (unless already changed)
            if ($pollAnswer->getChoice() === $this) {
                $pollAnswer->setChoice(null);
            }
        }

        return $this;
    }
}
