<?php

namespace App\Entity;

use App\Repository\AvisRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Enum\StatutAvis;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: AvisRepository::class)]
class Avis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['avis:employee', 'avis:public'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['avis:employee', 'avis:public'])]
    private ?int $note = null;

    #[ORM\Column(length: 255)]
    #[Groups(['avis:employee', 'avis:public'])]
    private ?string $description = null;

    #[ORM\Column(enumType: StatutAvis::class)]
    #[Groups(['avis:employee', 'avis:public'])]
    private StatutAvis $statut = StatutAvis::EN_ATTENTE;

    #[ORM\Column(type: 'datetime_immutable', options: ['default'=> 'CURRENT_TIMESTAMP'])]
    #[Groups(['avis:employee', 'avis:public'])]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue():void
    {
        if (!isset($this->createdAt)){
            $this->createdAt = new \DateTimeImmutable();
        }
    }
    #[ORM\Column(nullable: true,type: 'datetime_immutable', options: ['default'=> 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PreUpdate]
    public function setUpdatedAtValue():void
    {
        if (!isset($this->updatedAt)){
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    #[ORM\ManyToOne(inversedBy: 'avis')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['avis:employee'])]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['avis:employee'])]
    private ?Commande $commande = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNote(): ?int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

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

    public function getStatut(): StatutAvis
    {
        return $this->statut;
    }

    public function setStatut(StatutAvis $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    
    public function setUser(?User $user): self 
    {
        $this->user = $user; 

        return $this;
    }

    public function getCommande(): ?Commande 
    { 
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self 
    { 
        $this->commande = $commande; 

        return $this; 
    }
}
