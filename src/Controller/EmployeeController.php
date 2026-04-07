<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Attribute\Security;

#[Route('/api/employee/commande', name: 'api_employee_commande_')]
class EmployeeController extends AbstractController
{
    public function __construct(
        private CommandeRepository $commandeRepository,
        private EntityManagerInterface $em
    ) {}

    #[Route('/{id}/annulation', name: 'cancel', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[Security("is_granted('ROLE_EMPLOYEE')")]
    public function cancel(int $id, Request $request): JsonResponse
    {
        $commande = $this->commandeRepository->find($id);
        if (!$commande) {
            return $this->json(null, Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        $mode = $data['mode_contact'] ?? null; // 'gsm' | 'mail'
        $motif = trim((string)($data['motif'] ?? ''));

        if (!in_array($mode, ['gsm', 'mail'], true) || $motif === '') {
            return $this->json([
                'message' => "Champs requis: mode_contact (gsm|mail), motif"
            ], Response::HTTP_BAD_REQUEST);
        }

        // sécurité métier : annuler seulement si contact+motif
        $commande->setAnnulationModeContact($mode);
        $commande->setAnnulationMotif($motif);
        $commande->setAnnuleeAt(new \DateTimeImmutable());
        $commande->setStatut(\App\Enum\StatutCommande::ANNULEE);

        $this->em->flush();

        return $this->json($commande, Response::HTTP_OK, [], ['groups' => ['commande:read']]);
    }
}
