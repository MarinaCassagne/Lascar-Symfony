<?php

// Namespace du contrôleur (App\Controller)
namespace App\Controller;

// Import des classes utilisées
use App\Entity\Moderation;
use App\Form\ModerationType;
use App\Repository\ModerationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

// Route principale du contrôleur : toutes les routes commencent par /moderation
#[Route('/moderation')]
final class ModerationController extends AbstractController
{
    // Route pour afficher la liste des modérations
    // URL : /moderation
    // Nom : app_moderation_index
    // Méthode HTTP : GET
    #[Route(name: 'app_moderation_index', methods: ['GET'])]
    public function index(ModerationRepository $moderationRepository): Response
    {
        // Rendu de la vue index.html.twig
        // On envoie toutes les modérations récupérées depuis la base de données
        return $this->render('moderation/index.html.twig', [
            'moderations' => $moderationRepository->findAll(),
        ]);
    }

    // Route pour créer une nouvelle modération
    // URL : /moderation/new
    // Méthodes : GET (affichage du formulaire) et POST (envoi du formulaire)
    #[Route('/new', name: 'app_moderation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création d’un nouvel objet Moderation vide
        $moderation = new Moderation();

        // Création du formulaire basé sur ModerationType
        $form = $this->createForm(ModerationType::class, $moderation);

        // Récupération des données envoyées par le formulaire
        $form->handleRequest($request);

        // Vérifie si le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()) {

            // Prépare l'entité à être enregistrée en base de données
            $entityManager->persist($moderation);

            // Exécute réellement l'enregistrement
            $entityManager->flush();

            // Redirection vers la liste des modérations
            return $this->redirectToRoute('app_moderation_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire si non soumis ou invalide
        return $this->render('moderation/new.html.twig', [
            'moderation' => $moderation,
            'form' => $form,
        ]);
    }

    // Route pour afficher une modération spécifique
    // URL : /moderation/{id}
    // Méthode : GET
    #[Route('/{id}', name: 'app_moderation_show', methods: ['GET'])]
    public function show(Moderation $moderation): Response
    {
        // Symfony récupère automatiquement la modération grâce à l'id
        return $this->render('moderation/show.html.twig', [
            'moderation' => $moderation,
        ]);
    }

    // Route pour modifier une modération existante
    // URL : /moderation/{id}/edit
    // Méthodes : GET et POST
    #[Route('/{id}/edit', name: 'app_moderation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Moderation $moderation, EntityManagerInterface $entityManager): Response
    {
        // Création du formulaire avec les données existantes
        $form = $this->createForm(ModerationType::class, $moderation);

        // Récupération des données envoyées
        $form->handleRequest($request);

        // Si le formulaire est valide
        if ($form->isSubmitted() && $form->isValid()) {

            // Pas besoin de persist() car l'entité existe déjà
            $entityManager->flush();

            // Redirection vers la liste
            return $this->redirectToRoute('app_moderation_index', [], Response::HTTP_SEE_OTHER);
        }

        // Affiche le formulaire de modification
        return $this->render('moderation/edit.html.twig', [
            'moderation' => $moderation,
            'form' => $form,
        ]);
    }

    // Route pour supprimer une modération
    // URL : /moderation/{id}
    // Méthode : POST (sécurité)
    #[Route('/{id}', name: 'app_moderation_delete', methods: ['POST'])]
    public function delete(Request $request, Moderation $moderation, EntityManagerInterface $entityManager): Response
    {
        // Vérification du token CSRF pour éviter les attaques
        if ($this->isCsrfTokenValid(
            'delete' . $moderation->getId(),
            $request->getPayload()->getString('_token')
        )) {

            // Suppression de l'entité
            $entityManager->remove($moderation);
            $entityManager->flush();
        }

        // Redirection vers la liste après suppression
        return $this->redirectToRoute('app_moderation_index', [], Response::HTTP_SEE_OTHER);
    }
}
