<?php

namespace App\Entity;

use App\Repository\CarrierRepository;
use App\Service\SendcloudService;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarrierRepository::class)]
class Carrier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $codeTransporter = null;

    public function __toString()
    {
        $price = number_format($this->getPrice(), 2, ',', ' ') . ' €';
        return $this->getName().'<br/>'.$price.'<br/>'.$this->getDescription();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
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

    public function getCodeTransporter(): ?string
    {
        return $this->codeTransporter;
    }

    public function setCodeTransporter(?string $codeTransporter): static
    {
        $this->codeTransporter = $codeTransporter;
        return $this;
    }

    /**
     * Met à jour le Carrier en récupérant depuis l’API Sendcloud
     * l’option d’expédition la moins chère, et en ajustant
     * le codeTransporter, name, price, description, etc.
     */
    public function updateFromCheapestOption(
        SendcloudService $service,
        array $recipientParams,
        array $additionalParams = []
    ): void {
        $shippingOptions = $service->fetchShippingOptionsForRecipient($recipientParams, $additionalParams);

        if (empty($shippingOptions['data'])) {
            $this->setName('Aucune option trouvée');
            $this->setCodeTransporter(null);
            $this->setPrice(0);
            $this->setDescription('Pas d’option disponible');
            return;
        }

        $bestCode  = null;
        $bestPrice = null;
        $bestName  = null;

        foreach ($shippingOptions['data'] as $option) {
            if (!empty($option['quotes'])) {
                foreach ($option['quotes'] as $quote) {
                    $val = $quote['price']['total']['value'] ?? null;
                    if ($val !== null) {
                        $floatVal = (float)$val;
                        // On vérifie si c'est moins cher que la meilleure option trouvée
                        if ($bestPrice === null || $floatVal < $bestPrice) {
                            $bestPrice = $floatVal;
                            $bestCode  = $option['code'] ?? 'inconnu';
                            if (!empty($option['product']['name'])) {
                                $bestName = $option['product']['name'];
                            } else {
                                $bestName = $bestCode;
                            }
                        }
                    }
                }
            }
        }

        if ($bestCode !== null) {
            $this->setCodeTransporter($bestCode);
            $this->setName($bestName ?? $bestCode);
            $this->setPrice($bestPrice ?? 0.0);
            $this->setDescription('Option la moins chère trouvée via Sendcloud');
        } else {
            $this->setName('Option non trouvée');
            $this->setCodeTransporter(null);
            $this->setPrice(0);
            $this->setDescription('Aucune option compatible');
        }
    }
}
