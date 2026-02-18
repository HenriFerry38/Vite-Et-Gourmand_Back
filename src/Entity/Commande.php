<?php

namespace App\Entity;

use App\Entity\Menu;
use App\Entity\User;
use App\Enum\StatutCommande;
use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: CommandeRepository::class)]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['commande:read','avis:employee'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Groups(['commande:read','avis:employee'])]
    private ?string $numero_commande = null;

    #[ORM\Column(type: 'datetime_immutable', options: ['default'=> 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $date_commande;

    #[ORM\PrePersist]
    public function setDateCommandeValue():void
    {
        if (!isset($this->date_commande)){
            $this->date_commande = new \DateTimeImmutable();
        }
    }

    #[ORM\Column(length: 150)]
    #[Groups(['avis:employee'])]
    private ?string $adresse_prestation = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Groups(['commande:read','avis:employee'])]
    private ?\DateTimeInterface $date_prestation = null;

   #[ORM\Column(type: Types::TIME_MUTABLE)]
   #[Groups(['commande:read','avis:employee'])]
    private ?\DateTimeInterface $heure_prestation = null;

    #[ORM\Column(nullable :true, type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $prix_commande = null;

    #[ORM\Column]
    #[Groups(['commande:read','avis:employee'])]
    private ?int $nb_personne = null;

    #[ORM\Column(nullable: true, type: Types::DECIMAL, precision: 5, scale: 2)]
    private ?string $prix_livraison = null;

    #[ORM\Column(nullable :true, type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Groups(['commande:read','avis:employee'])]
    private ?string $prix_total = null;
    

    #[ORM\Column(enumType: StatutCommande::class)]
    private StatutCommande $statut = StatutCommande::EN_ATTENTE;

    #[ORM\Column(nullable: true)]
    private ?bool $pret_materiel = null;

    #[ORM\Column(nullable: true)]
    private ?bool $restitution_materiel = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'commandes')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['commande:read','avis:employee'])]
    private ?Menu $menu = null;

    #[ORM\Column(nullable: true, type: 'datetime_immutable')]
    #[Groups(['commande:read'])]
    private ?\DateTimeImmutable $annulee_at = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['commande:read'])]
    private ?string $annulation_mode_contact = null; // 'gsm' | 'mail'

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['commande:read'])]
    private ?string $annulation_motif = null;

    #[ORM\Column(nullable: true, type: 'datetime_immutable')]
    #[Groups(['commande:read'])]
    private ?\DateTimeImmutable $retour_materiel_at = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $statutUpdatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroCommande(): ?string
    {
        return $this->numero_commande;
    }

    public function setNumeroCommande(string $numero_commande): static
    {
        $this->numero_commande = $numero_commande;

        return $this;
    }

     public function getAdressePrestation(): ?string
    {
        return $this->adresse_prestation;
    }

    public function setAdressePrestation(string $adresse_prestation): static
    {
        $this->adresse_prestation = $adresse_prestation;

        return $this;
    }

    public function getDateCommande(): ?\DateTimeImmutable
    {
        return $this->date_commande;
    }
    
    public function setDateCommande(\DateTimeImmutable $date_commande): static
    {
        $this->date_commande = $date_commande;

        return $this;
    }

    public function getDatePrestation(): ?\DateTimeInterface
    {
        return $this->date_prestation;
    }

    public function setDatePrestation(\DateTimeInterface $date_prestation): static
    {
        $this->date_prestation = $date_prestation;

        return $this;
    }

    public function getHeurePrestation(): ?\DateTimeInterface
    {
        return $this->heure_prestation;
    }

    public function setHeurePrestation(\DateTimeInterface $heure_prestation): static
    {
        $this->heure_prestation = $heure_prestation;

        return $this;
    }

    public function getPrixCommande(): ?string
    {
        return $this->prix_commande;
    }

    public function setPrixCommande(string $prix_commande): static
    {
        $this->prix_commande = $prix_commande;

        return $this;
    }

     public function getPrixTotal(): ?string
    {
        return $this->prix_total;
    }

    public function setPrixTotal(string $prix_total): static
    {
        $this->prix_total = $prix_total;

        return $this;
    }
    
    public function getPrixTotalFloat(): float
    {
        return (float) ($this->prix_total ?? 0);
    }

    public function getNbPersonne(): ?int
    {
        return $this->nb_personne;
    }

    public function setNbPersonne(int $nb_personne): static
    {
        $this->nb_personne = $nb_personne;

        return $this;
    }

    public function getPrixLivraison(): ?string
    {
        return $this->prix_livraison;
    }

    public function setPrixLivraison(string $prix_livraison): static
    {
        $this->prix_livraison = $prix_livraison;

        return $this;
    }

    public function getStatut(): StatutCommande
    {
        return $this->statut;
    }

    public function setStatut(StatutCommande $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function isPretMateriel(): ?bool
    {
        return $this->pret_materiel;
    }

    public function setPretMateriel(?bool $pret_materiel): static
    {
        $this->pret_materiel = $pret_materiel;

        return $this;
    }

    public function isRestitutionMateriel(): ?bool
    {
        return $this->restitution_materiel;
    }

    public function setRestitutionMateriel(?bool $restitution_materiel): static
    {
        $this->restitution_materiel = $restitution_materiel;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getMenu(): ?Menu
    {
        return $this->menu;
    }

    public function setMenu(?Menu $menu): static
    {
        $this->menu = $menu;

        return $this;
    }
    
    public function getAnnuleeAt(): ?\DateTimeImmutable
    {
        return $this->annulee_at;
    }

    public function setAnnuleeAt(?\DateTimeImmutable $annulee_at): static
    {
        $this->annulee_at = $annulee_at;
        return $this;
    }

    public function getAnnulationModeContact(): ?string
    {
        return $this->annulation_mode_contact;
    }

    public function setAnnulationModeContact(?string $annulation_mode_contact): static
    {
        $this->annulation_mode_contact = $annulation_mode_contact;
        return $this;
    }

    public function getAnnulationMotif(): ?string
    {
        return $this->annulation_motif;
    }

    public function setAnnulationMotif(?string $annulation_motif): static
    {
        $this->annulation_motif = $annulation_motif;
        return $this;
    }

    public function getRetourMaterielAt(): ?\DateTimeImmutable
    {
        return $this->retour_materiel_at;
    }

    public function setRetourMaterielAt(?\DateTimeImmutable $retour_materiel_at): static
    {
        $this->retour_materiel_at = $retour_materiel_at;
        return $this;
    }

    public function getStatutUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->statutUpdatedAt;
    }

    public function setStatutUpdatedAt(?\DateTimeImmutable $dt): static
    {
        $this->statutUpdatedAt = $dt;
        return $this;
    }
}
