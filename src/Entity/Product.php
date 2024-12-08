<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length:255)]
    private ?string $name = null;

    #[ORM\Column(length:255)]
    private ?string $slug = null;

    #[ORM\Column(type:'text')]
    private ?string $description = null;

    #[ORM\Column(length:255, nullable:true)]
    private ?string $illustration = null;

    #[ORM\Column(type:'float')]
    private ?float $price = null;

    #[ORM\Column(type:'float')]
    private ?float $tva = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable:false)]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: SousCategory::class, inversedBy: 'products')]
    #[ORM\JoinColumn(nullable:true)]
    private ?SousCategory $sousCategory = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Review::class, cascade:['remove'])]
    private Collection $reviews;
    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private bool $isHomepage = false;

    // ... autres méthodes

    public function getIsHomepage(): bool
    {
        return $this->isHomepage;
    }

    public function setIsHomepage(bool $isHomepage): self
    {
        $this->isHomepage = $isHomepage;

        return $this;
    }

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
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

    public function getIllustration(): ?string
    {
        return $this->illustration;
    }

    public function setIllustration(?string $illustration): static
    {
        $this->illustration = $illustration;
        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;
        return $this;
    }

    public function getTva(): ?float
    {
        return $this->tva;
    }

    public function setTva(float $tva): static
    {
        $this->tva = $tva;
        return $this;
    }

    public function getPriceWt(): ?float
    {
        $coeff = 1 + ($this->tva / 100);
        return $this->price * $coeff;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getSousCategory(): ?SousCategory
    {
        return $this->sousCategory;
    }

    public function setSousCategory(?SousCategory $sousCategory): static
    {
        $this->sousCategory = $sousCategory;
        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setProduct($this);
        }
        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getProduct() === $this) {
                $review->setProduct(null);
            }
        }
        return $this;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function isHomepage(): ?bool
    {
        return $this->isHomepage;
    }

    public function setHomepage(bool $isHomepage): static
    {
        $this->isHomepage = $isHomepage;

        return $this;
    }
}
