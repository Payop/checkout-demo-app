<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: "`order`")]
class Order
{
    public const STATUS_NEW = 'new';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ACCEPTED = 'accepted';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $product;

    #[ORM\Column(type: "datetime")]
    private $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    private $updatedAt;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $payopInvoiceId;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private $payopTxid;

    #[ORM\Column(type: "string", length: 255)]
    private $status;

    public function __construct()
    {
        $this->status = self::STATUS_NEW;
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct(): Product
    {
        return $this->product;
    }

    /**
     * @param Product $product
     *
     * @return $this
     */
    public function setProduct(Product $product): self
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeInterface $updatedAt
     *
     * @return $this
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPayopInvoiceId(): ?string
    {
        return $this->payopInvoiceId;
    }

    /**
     * @param string|null $payopInvoiceId
     *
     * @return $this
     */
    public function setPayopInvoiceId(string $payopInvoiceId): self
    {
        $this->payopInvoiceId = $payopInvoiceId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPayopTxid(): ?string
    {
        return $this->payopTxid;
    }

    /**
     * @param string $payopTxid
     *
     * @return $this
     */
    public function setPayopTxid(string $payopTxid): self
    {
        $this->payopTxid = $payopTxid;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }
}
