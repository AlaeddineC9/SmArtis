<?php

namespace App\Entity;

use App\Repository\SousCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: SousCategoryRepository::class)]
#[Vich\Uploadable]
class SousCategory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length:255)]
    private ?string $name = null;

    #[ORM\Column(length:255)]
    private ?string $slug = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'sousCategories')]
    #[ORM\JoinColumn(nullable:false)]
    private ?Category $category = null;

    #[ORM\OneToMany(mappedBy: 'sousCategory', targetEntity: Product::class)]
    private Collection $products;

    /**
     * Nom du fichier image (stocké en base).
     */
    #[ORM\Column(length:255, nullable:true)]
    private ?string $illustration = null;

    /**
     * Champ d’upload (non mappé) pour VichUploader.
     */
    #[Vich\UploadableField(mapping: 'souscategory_illustration', fileNameProperty: 'illustration')]
    private ?File $illustrationFile = null;

    /**
     * Forcer la mise à jour (invalider le cache).
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

  

    public function __construct()
    {
        $this->products = new ArrayCollection();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
            $product->setSousCategory($this);
        }
        return $this;
    }

    public function removeProduct(Product $product): static
    {
        if ($this->products->removeElement($product)) {
            if ($product->getSousCategory() === $this) {
                $product->setSousCategory(null);
            }
        }
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

    public function getIllustrationFile(): ?File
    {
        return $this->illustrationFile;
    }

    public function setIllustrationFile(?File $file): static
    {
        $this->illustrationFile = $file;
        if ($file) {
            // MàJ forcée pour invalider le cache
            $this->updatedAt = new \DateTimeImmutable();
        }
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

    public function __toString()
    {
        return $this->name;
    }
}
