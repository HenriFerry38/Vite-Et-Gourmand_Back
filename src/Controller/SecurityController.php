<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api', name: 'app_api_')]
final class SecurityController extends AbstractController
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $manager,
        private UserRepository $repository,
        private RoleRepository $roleRepository,
        private MailerInterface $mailer
        )
    {
    }

    #[Route('/registration', name: 'registration', methods: ['POST'])]
    #[OA\Post(
        path: '/api/registration',
        summary: "Inscription d'un nouvel utilisateur",
        tags: ['Acces Public'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'prenom', type: 'string', example: 'Toto'),
                    new OA\Property(property: 'nom', type: 'string', example: 'Toto'),
                    new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'Mot de passe'),
                    new OA\Property(property: 'telephone', type: 'string', example: '0612345678'),
                    new OA\Property(property: 'adresse', type: 'string', example: '1 rue de l adresse'),
                    new OA\Property(property: 'code_postal', type: 'string', example: '12345'),
                    new OA\Property(property: 'ville', type: 'string', example: 'Ville-city'),
                    new OA\Property(property: 'pays', type: 'string', example: 'France'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Utilisateur inscrit avec succès',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'user', type: 'string', example: "Nom d'utilisateur"),
                        new OA\Property(property: 'apiToken', type: 'string', example: '31a023e...'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER')),
                    ]
                )
            )
        ]
    )]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');
        $user->setPassword($passwordHasher->hashPassword($user, $user->getPassword()));
        $user->setCreatedAt(new \DateTimeImmutable());

        $roleUser = $this->roleRepository->findOneBy(['code' => 'ROLE_USER']);
        if (!$roleUser) {
            return new JsonResponse(['message' => 'ROLE_USER introuvable en base (table role)'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $user->addRoleEntity($roleUser);
        
        $this->manager->persist($user);
        $this->manager->flush();
        $emailTo = $user->getEmail();

        if ($emailTo) {
            $email = (new Email())
                ->from('no-reply@viteetgourmand.fr')
                ->to($emailTo)
                ->subject('Bienvenue chez Vite & Gourmand 🎉')
                ->text(
                    "Bonjour {$user->getPrenom()},\n\n" .
                    "Ton compte a bien été créé ✅\n" .
                    "Tu peux maintenant réserver tes menus et suivre tes commandes.\n\n" .
                    "À très vite,\n— Vite & Gourmand"
                );

            $this->mailer->send($email);
        }

        return new JsonResponse(
            ['user' => $user->getUserIdentifier(), 'apiToken' => $user->getApiToken(), 'roles' => $user->getRoles()],
            Response::HTTP_CREATED);
    }

    #[Route('/login', name: 'login', methods:['POST'])]
    #[OA\Post(
        path: '/api/login',
        summary: "Connecter un utilisateur",
        tags: ['Acces Public'],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Données de l’utilisateur pour se connecter",
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(
                        property: 'username',
                        type: 'string',
                        example: 'adresse@email.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        example: 'Mot de passe'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
            response: 401,
            description: 'Identifiants invalides'
            ),
            new OA\Response(
                response: 400,
                description: 'JSON invalide'
            ),
            new OA\Response(
            response: 403,
            description: 'Compte désactivé'
            ),
            new OA\Response(
                response: 200,
                description: 'Connexion réussie',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'user',
                            type: 'string',
                            example: "Nom d'utilisateur"
                        ),
                        new OA\Property(
                            property: 'apiToken',
                            type: 'string',
                            example: '31a023e212f116124a36af14ea0c1c3806eb9378'
                        ),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(
                                type: 'string',
                                example: 'ROLE_USER'
                            )
                        ),
                    ]
                )
            )
        ]
    )]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return new JsonResponse(['message' => 'Informations d\'identification manquantes'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isActive()) {
            return new JsonResponse(['message' => 'Compte désactivé'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'user' => $user->getUserIdentifier(),
            'apiToken' => $user->getApiToken(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/account/me', name: 'me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/account/me',
        summary: "Récupérer l'utilisateur connecté",
        tags: ['Utilisateur'],
        security: [
            ['X-AUTH-TOKEN' => []]
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur authentifié",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'email', type: 'string', example: 'adresse@email.com'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER')),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', example: '2026-01-01T10:00:00+01:00'),
                        new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time', example: '2026-01-03T12:00:00+01:00'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Non authentifié')
                    ]
                )
            ),
        ]
    )]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
           return new JsonResponse(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'adresse' => $user->getAdresse(),
            'ville' => $user->getVille(),
            'codePostal' => $user->getCodePostal(),
            'pays' => $user->getPays(),
            'roles' => $user->getRoles(),
        ]);   
    }
    
    #[Route('/account/me', name:'edit', methods:['PUT'])]
    #[OA\Put(
        path: '/api/account/me',
        summary: "Mettre à jour l'utilisateur connecté",
        tags: ['Utilisateur'],
        security: [
            ['X-AUTH-TOKEN' => []]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Champs à mettre à jour (le mot de passe est optionnel, s'il est présent il sera re-hashé)",
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'prenom', type: 'string', example: 'NouveauPrénom'),
                    new OA\Property(property: 'nom', type: 'string', example: 'NouveauNom'),
                    new OA\Property(property: 'email', type: 'string', example: 'Nouvelleadresse@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'NouveauMotdepasse'),
                    new OA\Property(property: 'telephone', type: 'string', example: '0612345678'),
                    new OA\Property(property: 'adresse', type: 'string', example: '1 rue de l adresse'),
                    new OA\Property(property: 'code_postal', type: 'string', example: '12345'),
                    new OA\Property(property: 'ville', type: 'string', example: 'Nouvelle Ville'),
                    new OA\Property(property: 'pays', type: 'string', example: 'Nouveau Pays'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 204,
                description: "Utilisateur mis à jour (aucun contenu retourné)"
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Non authentifié')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: "Requête invalide (JSON invalide)"
            ),
        ]
    )]
    public function edit(
        Request $request,
        #[CurrentUser] ?User $user,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse
    {
        if (!$user){
            return new JsonResponse(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        $this->serializer->deserialize(
        $request->getContent(),
        User::class,
        'json',
        [
            AbstractNormalizer::OBJECT_TO_POPULATE => $user,
        ]
        );

        //Si un mot de pass est présent on le re-hash
        $data = json_decode($request->getContent(), true);
        if (!Empty($data['password'])) {

            $user->setPassword($passwordHasher->hashPassword($user, $data['password']));
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->manager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/account/me', name: 'delete', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/account/me',
        summary: "Supprimer l'utilisateur connecté",
        description: "Supprime définitivement le compte de l'utilisateur actuellement authentifié.",
        tags: ['Utilisateur'],
        security: [['X-AUTH-TOKEN' => []]],
        responses: [
            new OA\Response(
                response: 204,
                description: "Compte supprimé (aucun contenu retourné)"
            ),
            new OA\Response(
                response: 401,
                description: "Non authentifié",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Non authentifié')
                    ]
                )
            ),
            new OA\Response(
                response: 409,
                description: "Conflit: suppression impossible (contraintes de relations en base)",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: "Suppression impossible: des données liées empêchent la suppression.")
                    ]
                )
            ),
        ]
    )]
    public function deleteMe(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $this->manager->remove($user);
            $this->manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } catch (\Throwable $e) {
            // Souvent: contraintes FK (commandes/avis liés) si pas de cascade ou SET NULL
            return new JsonResponse(
                ['message' => "Suppression impossible: des données liées empêchent la suppression."],
                Response::HTTP_CONFLICT
            );
        }
    }
}

