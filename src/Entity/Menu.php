<?php

namespace App\Entity;

use App\Repository\MenuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\HasLifecycleCallbacks]
#[ORM\Entity(repositoryClass: MenuRepository::class)]
class Menu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['menu:read', 'menu:detail'])]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Groups(['menu:read', 'menu:detail','commande:read','avis:employee'])]
    private ?string $titre = null;

    #[ORM\Column]
    #[Groups(['menu:read', 'menu:detail'])]
    private ?int $nb_personne_mini = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2)]
    #[Groups(['menu:read', 'menu:detail'])]
    private ?string $prix_par_personne = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['menu:read', 'menu:detail'])]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['menu:read', 'menu:detail'])]
    private ?int $quantite_restaurant = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['menu:read', 'menu:detail','commande:read'])]
    private bool $pret_materiel = false;

    #[ORM\ManyToOne(inversedBy: 'menus')]
    #[Groups(['menu:read', 'menu:detail'])]
    private ?Regime $regime = null;

    #[ORM\ManyToOne(inversedBy: 'menus')]
    #[Groups(['menu:read', 'menu:detail'])]
    private ?Theme $theme = null;

    #[ORM\Column(type: 'datetime_immutable', options: ['default'=> 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function onPrePersist():void
    {
        if (!isset($this->createdAt)){
            $this->createdAt = new \DateTimeImmutable();
        }
    }
    #[ORM\Column(nullable: true,type: 'datetime_immutable', options: ['default'=> 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PreUpdate]
    public function onPreUpdate():void
    {
        if (!isset($this->updatedAt)){
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    /**
     * @var Collection<int, Plat>
     */
    #[ORM\ManyToMany(targetEntity: Plat::class, inversedBy: 'menus')]
    #[Groups(['menu:read', 'menu:detail'])]
    private Collection $plats;

    /**
     * @var Collection<int, Commande>
     */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'menu')]
    private Collection $commandes;

    public function __construct()
    {
        $this->plats = new ArrayCollection();
        $this->commandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getNbPersonneMini(): ?int
    {
        return $this->nb_personne_mini;
    }

    public function setNbPersonneMini(int $nb_personne_mini): static
    {
        $this->nb_personne_mini = $nb_personne_mini;

        return $this;
    }

    public function getPrixParPersonne(): ?string
    {
        return $this->prix_par_personne;
    }

    public function setPrixParPersonne(string $prix_par_personne): static
    {
        $this->prix_par_personne = $prix_par_personne;

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

    public function getQuantiteRestaurant(): ?int
    {
        return $this->quantite_restaurant;
    }

    public function setQuantiteRestaurant(?int $quantite_restaurant): static
    {
        $this->quantite_restaurant = $quantite_restaurant;

        return $this;
    }

    public function isPretMateriel(): bool
    {
        return $this->pret_materiel;
    }

    public function setPretMateriel(bool $pret_materiel): static
    {
        $this->pret_materiel = $pret_materiel;
        return $this;
    }
    public function getRegime(): ?Regime
    {
        return $this->regime;
    }

    public function setRegime(?Regime $regime): static
    {
        $this->regime = $regime;

        return $this;
    }

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

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

    /**
     * @return Collection<int, Plat>
     */
    public function getPlats(): Collection
    {
        return $this->plats;
    }

    public function addPlat(Plat $plat): static
    {
        if (!$this->plats->contains($plat)) {
            $this->plats->add($plat);
        }

        return $this;
    }

    public function removePlat(Plat $plat): static
    {
        $this->plats->removeElement($plat);

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setMenu($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getMenu() === $this) {
                $commande->setMenu(null);
            }
        }

        return $this;
    }
}
