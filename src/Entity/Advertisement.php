<?php

namespace App\Entity;

use App\Repository\AdvertisementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdvertisementRepository::class)]
class Advertisement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $placeNumber = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private array $sides = [];

    #[ORM\ManyToOne(targetEntity: AdvertisementType::class, inversedBy: 'advertisements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AdvertisementType $type = null;

    #[ORM\OneToOne(mappedBy: 'advertisement', targetEntity: AdvertisementLocation::class, cascade: ['persist','remove'])]
    private ?AdvertisementLocation $location = null;

    #[ORM\OneToMany(mappedBy: 'advertisement', targetEntity: AdvertisementBooking::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $bookings;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $sideADescription = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $sideBDescription = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $sideAPrice = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $sideBPrice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sideAImage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sideBImage = null;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code === null ? null : (string) $code;

        return $this;
    }

    public function getPlaceNumber(): ?string
    {
        return $this->placeNumber;
    }

    public function setPlaceNumber(?string $placeNumber): self
    {
        $this->placeNumber = $placeNumber === null ? null : (string) trim($placeNumber);

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address === null ? null : (string) trim($address);

        return $this;
    }

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
            if ($s === null) {
                continue;
            }
            $s = mb_strtoupper(trim((string) $s));
            if ($s === '') {
                continue;
            }
            $normalized[] = $s;
        }

        $this->sides = array_values(array_unique($normalized));

        return $this;
    }

    public function addSide(string $side): self
    {
        $side = mb_strtoupper(trim($side));
        if ($side === '') {
            return $this;
        }

        $sides = $this->getSides();
        if (!in_array($side, $sides, true)) {
            $sides[] = $side;
            $this->sides = $sides;
        }

        return $this;
    }

    /**
     * @param string[] $sides
     */
    public function mergeSides(array $sides): self
    {
        $current = $this->getSides();
        $incoming = [];
        foreach ($sides as $s) {
            if ($s === null) {
                continue;
            }
            $s = mb_strtoupper(trim((string) $s));
            if ($s === '') {
                continue;
            }
            $incoming[] = $s;
        }
        $this->sides = array_values(array_unique(array_merge($current, $incoming)));

        return $this;
    }

    public function getType(): ?AdvertisementType
    {
        return $this->type;
    }

    public function setType(?AdvertisementType $type): self
    {
        $this->type = $type;

        return $this;
    }

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

    /**
     * @return Collection<int, AdvertisementBooking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(AdvertisementBooking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setAdvertisement($this);
        }

        return $this;
    }

    public function removeBooking(AdvertisementBooking $booking): static
    {
        if ($this->bookings->removeElement($booking) && $booking->getAdvertisement() === $this) {
            $booking->setAdvertisement(null);
        }

        return $this;
    }

    public function getSideADescription(): ?string
    {
        return $this->sideADescription;
    }

    public function setSideADescription(?string $sideADescription): static
    {
        $this->sideADescription = $sideADescription;

        return $this;
    }

    public function getSideBDescription(): ?string
    {
        return $this->sideBDescription;
    }

    public function setSideBDescription(?string $sideBDescription): static
    {
        $this->sideBDescription = $sideBDescription;

        return $this;
    }

    public function getSideAPrice(): ?string
    {
        return $this->sideAPrice;
    }

    public function setSideAPrice(?string $sideAPrice): static
    {
        $this->sideAPrice = $sideAPrice;

        return $this;
    }

    public function getSideBPrice(): ?string
    {
        return $this->sideBPrice;
    }

    public function setSideBPrice(?string $sideBPrice): static
    {
        $this->sideBPrice = $sideBPrice;

        return $this;
    }

    public function getSideAImage(): ?string
    {
        return $this->sideAImage;
    }

    public function setSideAImage(?string $sideAImage): static
    {
        $this->sideAImage = $sideAImage;

        return $this;
    }

    public function getSideBImage(): ?string
    {
        return $this->sideBImage;
    }

    public function setSideBImage(?string $sideBImage): static
    {
        $this->sideBImage = $sideBImage;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->location?->getLatitude();
    }

    public function setLatitude(?float $latitude): static
    {
        $location = $this->getOrCreateLocation();
        $location->setLatitude($latitude);

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->location?->getLongitude();
    }

    public function setLongitude(?float $longitude): static
    {
        $location = $this->getOrCreateLocation();
        $location->setLongitude($longitude);

        return $this;
    }

    public function getCategoryName(): ?string
    {
        return $this->type?->getCategory()?->getName();
    }

    public function __toString(): string
    {
        $parts = array_filter([$this->getPlaceNumber(), $this->getAddress()]);

        return $parts !== [] ? implode(' — ', $parts) : sprintf('Конструкция #%d', $this->id ?? 0);
    }

    private function getOrCreateLocation(): AdvertisementLocation
    {
        if ($this->location === null) {
            $this->location = (new AdvertisementLocation())->setAdvertisement($this);
        }

        return $this->location;
    }
}
