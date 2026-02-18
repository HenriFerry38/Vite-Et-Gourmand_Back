<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/employees', name: 'app_api_admin_employees_')]
final class AdminController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private RoleRepository $roleRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
    ) {}

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Post(
        path: '/api/admin/employees',
        summary: "Créer un compte employé",
        description: "L’admin crée un compte employé (email + mot de passe). Un mail est envoyé à l’employé sans communiquer le mot de passe.",
        tags: ['Admin'],
        security: [['X-AUTH-TOKEN' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', example: 'employe@site.fr'),
                    new OA\Property(property: 'password', type: 'string', example: 'MotDePasseTemporaire!'),
                    new OA\Property(property: 'prenom', type: 'string', example: 'Julie'),
                    new OA\Property(property: 'nom', type: 'string', example: 'Martin'),
                    new OA\Property(property: 'telephone', type: 'string', example: '0601020304'),
                    new OA\Property(property: 'adresse', type: 'string', example: '12 rue des Fleurs'),
                    new OA\Property(property: 'codePostal', type: 'integer', example: 38000),
                    new OA\Property(property: 'ville', type: 'string', example: 'Grenoble'),
                    new OA\Property(property: 'pays', type: 'string', example: 'France'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Compte employé créé",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 10),
                        new OA\Property(property: 'email', type: 'string', example: 'employe@site.fr'),
                        new OA\Property(property: 'message', type: 'string', example: 'Employé créé et email envoyé')
                    ]
                )
            ),
            new OA\Response(response: 400, description: "JSON invalide / champs manquants"),
            new OA\Response(response: 409, description: "Email déjà utilisé"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé"),
        ]
    )]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new JsonResponse(['message' => 'JSON invalide'], Response::HTTP_BAD_REQUEST);
        }

        $email = $data['email'] ?? null;
        $plainPassword = $data['password'] ?? null;

        if (!is_string($email) || $email === '' || !is_string($plainPassword) || $plainPassword === '') {
            return new JsonResponse(['message' => 'email et password sont obligatoires'], Response::HTTP_BAD_REQUEST);
        }

        if ($this->userRepository->findOneBy(['email' => $email])) {
            return new JsonResponse(['message' => 'Email déjà utilisé'], Response::HTTP_CONFLICT);
        }

        $roleEmployee = $this->roleRepository->findOneBy(['code' => 'ROLE_EMPLOYEE']);
        if (!$roleEmployee) {
            return new JsonResponse(['message' => 'ROLE_EMPLOYEE introuvable en base'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setIsActive(true);

        // Champs requis chez toi (adapte si certains doivent être nullable)
        $user->setPrenom((string)($data['prenom'] ?? ''));
        $user->setNom((string)($data['nom'] ?? ''));
        $user->setTelephone((string)($data['telephone'] ?? '0000000000'));
        $user->setAdresse((string)($data['adresse'] ?? ''));
        $user->setCodePostal((int)($data['codePostal'] ?? 0));
        $user->setVille((string)($data['ville'] ?? ''));
        $user->setPays((string)($data['pays'] ?? ''));

        // Hash password
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        // Attache le rôle Employé (ManyToMany)
        $user->addRoleEntity($roleEmployee);

        $this->em->persist($user);
        $this->em->flush();

        // Envoi mail SANS mot de passe
        $mail = (new Email())
            ->from('no-reply@viteetgourmand.fr')
            ->to($user->getEmail())
            ->subject('Votre compte employé a été créé')
            ->text(
                "Bonjour {$user->getPrenom()},\n\n".
                "Un compte employé a été créé pour vous.\n".
                "Identifiant (email) : {$user->getEmail()}\n\n".
                "Le mot de passe n’est pas communiqué par email.\n".
                "Merci de vous rapprocher de l’administrateur pour l’obtenir.\n\n".
                "Veuillez une fois le mot de passe obtenu, modifier vos informations personelles.\n\n".
                "Bonne journée."
            );

        $this->mailer->send($mail);

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'message' => 'Employé créé et email envoyé',
        ], Response::HTTP_CREATED);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Get(
        path: '/api/admin/employees',
        summary: "Lister les comptes employé",
        tags: ['Admin'],
        security: [['X-AUTH-TOKEN' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des employés",
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 10),
                            new OA\Property(property: 'email', type: 'string', example: 'employe@site.fr'),
                            new OA\Property(property: 'isActive', type: 'boolean', example: true),
                            new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['ROLE_EMPLOYEE']),
                        ]
                    )
                )
            )
        ]
    )]
    public function list(): JsonResponse
    {
        $users = $this->userRepository->findAll();

        $employees = [];
        foreach ($users as $u) {
            if (in_array('ROLE_EMPLOYEE', $u->getRoles(), true)) {
                $employees[] = [
                    'id' => $u->getId(),
                    'email' => $u->getEmail(),
                    'isActive' => $u->isActive(),
                    'roles' => $u->getRoles(),
                ];
            }
        }

        return new JsonResponse($employees, Response::HTTP_OK);
    }


    #[Route('/{id}/disable', name: 'disable', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Patch(
        path: '/api/admin/employees/{id}/disable',
        summary: "Désactiver un compte employé",
        description: "Rend le compte inutilisable (ex: départ de l’entreprise).",
        tags: ['Admin'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 10))
        ],
        responses: [
            new OA\Response(response: 204, description: "Compte désactivé"),
            new OA\Response(response: 404, description: "Utilisateur introuvable"),
            new OA\Response(response: 401, description: "Non authentifié"),
            new OA\Response(response: 403, description: "Accès refusé"),
        ]
    )]
    public function disable(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $user->setIsActive(false);
        $user->setUpdatedAt(new DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/enable', name: 'enable', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Patch(
        path: '/api/admin/employees/{id}/enable',
        summary: "Réactiver un compte employé",
        tags: ['Admin'],
        security: [['X-AUTH-TOKEN' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 10))
        ],
        responses: [
            new OA\Response(response: 204, description: "Compte réactivé"),
            new OA\Response(response: 404, description: "Utilisateur introuvable"),
        ]
    )]
    public function enable(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $user->setIsActive(true);
        $user->setUpdatedAt(new DateTimeImmutable());
        $this->em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
