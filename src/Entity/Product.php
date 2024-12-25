<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[Vich\Uploadable]
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

    #[ORM\ManyToOne(targetEntity: SousCategory::class, inversedBy: 'products', fetch: "EAGER")]
    #[ORM\JoinColumn(nullable:true)]
    private ?SousCategory $sousCategory = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Review::class, cascade:['remove'])]
    private Collection $reviews;
    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private bool $isHomepage = false;

    #[ORM\Column(type:'float', nullable:true)]
    private ?float $weight = null;

    #[ORM\Column(length:255, nullable:true)]
    private ?string $dimensions = null;

    #[Vich\UploadableField(mapping: 'product_illustration', fileNameProperty: 'illustration')]
    private ?File $illustrationFile = null;

    #[ORM\Column(type:'datetime', nullable:true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, ProductMedia>
     */
    #[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductMedia::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $medias;
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
        $this->medias = new ArrayCollection();
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

    public function setIllustrationFile(?File $file): self
    {
        $this->illustrationFile = $file;
        if ($file) {
            // Force la mise à jour pour déclencher la re-persistence
            $this->updatedAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getIllustrationFile(): ?File
    {
        return $this->illustrationFile;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): static
    {
        $this->weight = $weight;
        return $this;
    }

    public function getDimensions(): ?string
    {
        return $this->dimensions;
    }

    public function setDimensions(?string $dimensions): static
    {
        $this->dimensions = $dimensions;
        return $this;
    }

    /**
     * @return Collection<int, ProductMedia>
     */
    public function getMedias(): Collection
    {
        return $this->medias;
    }

    public function addMedia(ProductMedia $media): static
    {
        if (!$this->medias->contains($media)) {
            $this->medias->add($media);
            $media->setProduct($this);
        }
        return $this;
    }

    public function removeMedia(ProductMedia $media): static
    {
        if ($this->medias->removeElement($media)) {
            if ($media->getProduct() === $this) {
                $media->setProduct(null);
            }
        }
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

    
    public function isHomepage(): ?bool
    {
        return $this->isHomepage;
    }
    
    public function setHomepage(bool $isHomepage): static
    {
        $this->isHomepage = $isHomepage;
        
        return $this;
    }
    public function __toString()
    {
        return $this->name;
    }
}
