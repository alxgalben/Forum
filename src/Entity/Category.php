<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $published = null;

    #[ORM\OneToMany(mappedBy: 'category', targetEntity: CategoryModerator::class)]
    private Collection $categoryModerators;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->categoryModerators = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setCategory($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getCategory() === $this) {
                $post->setCategory(null);
            }
        }

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

    /**
     * @return Collection<int, CategoryModerator>
     */
    public function getCategoryModerators(): Collection
    {
        return $this->categoryModerators;
    }

    public function addCategoryModerator(CategoryModerator $categoryModerator): static
    {
        if (!$this->categoryModerators->contains($categoryModerator)) {
            $this->categoryModerators->add($categoryModerator);
            $categoryModerator->setCategory($this);
        }

        return $this;
    }

    public function removeCategoryModerator(CategoryModerator $categoryModerator): static
    {
        if ($this->categoryModerators->removeElement($categoryModerator)) {
            // set the owning side to null (unless already changed)
            if ($categoryModerator->getCategory() === $this) {
                $categoryModerator->setCategory(null);
            }
        }

        return $this;
    }
}
