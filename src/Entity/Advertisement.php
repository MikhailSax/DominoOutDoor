<?php

namespace App\Entity;

use App\Repository\AdvertisementRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdvertisementRepository::class)]
class Advertisement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // произвольный код (можно хранить placeNumber или комбинировать)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    // номер места (n места)
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $placeNumber = null;

    // адрес размещения
    #[ORM\Column(length: 500, nullable: true)]
    private ?string $address = null;

    // стороны — JSON массив, например ["A","B"]
    #[ORM\Column(type: 'json', nullable: true)]
    private array $sides = [];

    // связь на тип
    #[ORM\ManyToOne(targetEntity: AdvertisementType::class, inversedBy: 'advertisements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AdvertisementType $type = null;

    // связь на локацию (one-to-one)
    #[ORM\OneToOne(mappedBy: 'advertisement', targetEntity: AdvertisementLocation::class, cascade: ['persist','remove'])]
    private ?AdvertisementLocation $location = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // code
    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code === null ? null : (string)$code;
        return $this;
    }

    // placeNumber
    public function getPlaceNumber(): ?string
    {
        return $this->placeNumber;
    }

    public function setPlaceNumber(?string $placeNumber): self
    {
        $this->placeNumber = $placeNumber === null ? null : (string)trim($placeNumber);
        return $this;
    }

    // address
    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address === null ? null : (string)trim($address);
        return $this;
    }

    // sides (json array)
    /**
     * @return string[]
     */
    public function getSides(): array
    {
        return $this->sides ?? [];
    }

    /**
     * @param string[] $sides
     */
    public function setSides(array $sides): self
    {
        $normalized = [];
        foreach ($sides as $s) {
            if ($s === null) continue;
            $s = mb_strtoupper(trim((string)$s));
            if ($s === '') continue;
            $normalized[] = $s;
        }
        $normalized = array_values(array_unique($normalized));
        $this->sides = $normalized;
        return $this;
    }

    /**
     * Добавить одну сторону (A или B)
     */
    public function addSide(string $side): self
    {
        $side = mb_strtoupper(trim($side));
        if ($side === '') return $this;
        $sides = $this->getSides();
        if (!in_array($side, $sides, true)) {
            $sides[] = $side;
            $this->sides = $sides;
        }
        return $this;
    }

    /**
     * Объединить/слить стороны (используется при обновлении/merge)
     * @param string[] $sides
     */
    public function mergeSides(array $sides): self
    {
        $current = $this->getSides();
        $incoming = [];
        foreach ($sides as $s) {
            if ($s === null) continue;
            $s = mb_strtoupper(trim((string)$s));
            if ($s === '') continue;
            $incoming[] = $s;
        }
        $this->sides = array_values(array_unique(array_merge($current, $incoming)));
        return $this;
    }

    // type
    public function getType(): ?AdvertisementType
    {
        return $this->type;
    }

    public function setType(?AdvertisementType $type): self
    {
        $this->type = $type;
        return $this;
    }

    // location
    public function getLocation(): ?AdvertisementLocation
    {
        return $this->location;
    }

    public function setLocation(?AdvertisementLocation $location): self
    {
        if ($location !== null && $location->getAdvertisement() !== $this) {
            $location->setAdvertisement($this);
        }
        $this->location = $location;
        return $this;
    }
}
