<?php

namespace App\Entity;

use App\Repository\ProductMediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ProductMediaRepository::class)]
#[Vich\Uploadable]
class ProductMedia
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du fichier stocké en BDD (ex: "2023-10-25-abcdef.jpg").
     */
    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    /**
     * Indique si c'est une vidéo ou non.
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $isVideo = false;

    /**
     * Fichier à uploader (pas mappé dans la BDD).
     */
    #[Vich\UploadableField(mapping: 'product_media', fileNameProperty: 'fileName')]
    private ?File $file = null;

    /**
     * Date de mise à jour pour invalider le cache.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * Relation ManyToOne vers Product
     */
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function isVideo(): bool
    {
        return $this->isVideo;
    }

    public function setIsVideo(bool $isVideo): static
    {
        $this->isVideo = $isVideo;
        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;

        if ($file) {
            // Force la mise à jour updatedAt lors d'un nouvel upload
            $this->updatedAt = new \DateTime('now');
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function __toString(): string
    {
        return $this->fileName ?? '';
    }
}
