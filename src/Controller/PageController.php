<?php

namespace App\Controller;

use App\Entity\Empleado;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\EmpleadoFormType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class PageController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        return $this->render('page/index.html.twig'
        );
    }

    #[Route('/dar-alta', name: 'alta')]
    public function alta(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
    {
        $empleado = new Empleado();
        $form = $this->createForm(EmpleadoFormType::class, $empleado);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Procesar la carga de la imagen
            $foto = $form->get('foto')->getData();
            if ($foto) {
                $originalFilename = pathinfo($foto->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$foto->guessExtension();
                $foto->setFoto($newFilename);
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/empleados';
                $foto->move($uploadDir, $newFilename);
            }

            // Guardar la entidad en la base de datos
            $form = $form->getData();

            $entityManager = $doctrine->getManager();
            $entityManager->persist($empleado);
            $entityManager->flush();

            // Redirigir o mostrar una confirmación
            return $this->redirectToRoute('ficha-empleado');
        }

        return $this->render('page/alta.html.twig', [
            'form' => $form->createView()
        ]
        );
    }

    #[Route('/ficha-empleado', name: 'ficha-empleado')]
    public function ficha_empleado(): Response
    {
        return $this->render('page/index.html.twig'
        );
    }
    
    #[Route('/baja-empleado', name: 'baja')]
    public function baja(): Response
    {
        return $this->render('page/index.html.twig'
        );
    }
}
