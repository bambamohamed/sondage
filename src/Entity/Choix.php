<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ChoixRepository")
 */
class Choix
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $libelle;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Question")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Question;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Votant")
     */
    private $votants;

    public function __construct()
    {
        $this->votants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->Question;
    }

    public function setQuestion(?Question $Question): self
    {
        $this->Question = $Question;

        return $this;
    }

    /**
     * @return Collection|Votant[]
     */
    public function getVotants(): Collection
    {
        return $this->votants;
    }

    public function addVotant(Votant $votant): self
    {
        if (!$this->votants->contains($votant)) {
            $this->votants[] = $votant;
        }

        return $this;
    }

    public function removeVotant(Votant $votant): self
    {
        if ($this->votants->contains($votant)) {
            $this->votants->removeElement($votant);
        }

        return $this;
    }
    public function __toString()
    {
        return $this->libelle;
    }
}
