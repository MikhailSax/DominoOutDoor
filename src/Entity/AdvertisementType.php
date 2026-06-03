<?php

namespace App\Entity;

use App\Repository\AdvertisementTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdvertisementTypeRepository::class)]
class AdvertisementType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $oneCRef = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $oneCData = null;

    #[ORM\ManyToOne(targetEntity: AdvertisementCategory::class, inversedBy: 'types')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AdvertisementCategory $category = null;

    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Advertisement::class)]
    private Collection $advertisements;

    public function __construct()
    {
        $this->advertisements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getOneCRef(): ?string
    {
        return $this->oneCRef;
    }

    public function setOneCRef(?string $oneCRef): self
    {
        $this->oneCRef = $oneCRef === null ? null : trim($oneCRef);

        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getOneCData(): ?array
    {
        return $this->oneCData;
    }

    /** @param array<string, mixed>|null $oneCData */
    public function setOneCData(?array $oneCData): self
    {
        $this->oneCData = $oneCData;

        return $this;
    }

    public function getCategory(): ?AdvertisementCategory
    {
        return $this->category;
    }

    public function setCategory(?AdvertisementCategory $category): self
    {
        $this->category = $category;
        return $this;
    }

    /** @return Collection<int, Advertisement> */
    public function getAdvertisements(): Collection
    {
        return $this->advertisements;
    }


    public function __toString(): string
    {
        return $this->name;
    }
}
