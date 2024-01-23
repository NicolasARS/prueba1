<?php

namespace App\Controller;

use App\Entity\Empleado;
use App\Entity\Seccion;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\EmpleadoFormType;
use App\Form\SeccionFormType;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;

class PageController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(ManagerRegistry $doctrine): Response
    {
        $empleadoRepo = $doctrine->getRepository(Empleado::Class);
        $empleados = $empleadoRepo->findAll();

        return $this->render('page/index.html.twig', [
            'empleados' => $empleados
        ]
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

                // Mover el archivo subido
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/empleados';
                $foto->move($uploadDir, $newFilename);

                // Asignar el nuevo nombre de archivo a la propiedad de la entidad Empleado
                $empleado->setFoto($newFilename);
    }

    // Guardar la entidad en la base de datos
    $entityManager = $doctrine->getManager();
    $entityManager->persist($empleado);
    $entityManager->flush();
    $empleadoId = $empleado->getId();

    // Redirigir o mostrar una confirmación
    return $this->redirectToRoute('ficha-empleado', ['id' => $empleadoId]);
        }

        return $this->render('page/alta.html.twig', [
            'form' => $form->createView()
        ]
        );
    }

    #[Route('/ficha-empleado/{id}', name: 'ficha-empleado')]
    public function ficha_empleado(ManagerRegistry $doctrine, $id): Response
    {
        $empleadoRepo = $doctrine->getRepository(Empleado::Class);
        $empleado = $empleadoRepo->find($id);

        return $this->render('page/ficha_empleado.html.twig', [
            'empleado' => $empleado
        ]
        );
    }
    
    #[Route('/secciones', name: 'secciones')]
    public function secciones(ManagerRegistry $doctrine, Request $request): Response
    {
        $seccion = new Seccion();
        $form = $this->createForm(SeccionFormType::class, $seccion);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            
        $entityManager = $doctrine->getManager();
        $entityManager->persist($seccion);
        $entityManager->flush();
        }
        
        return $this->render('page/secciones.html.twig', [
            'form' => $form->createView()
        ]
        );
    }

    #[Route('/empleado/eliminar/{id}', name: 'eliminar_empleado')]
    public function delete(ManagerRegistry $doctrine, $id): Response{
        $entityManager = $doctrine->getManager();
        $repositorio = $doctrine->getRepository(Empleado::class);
        $empleado = $repositorio->find($id);
        if ($empleado){
            try
            {
                $entityManager->remove($empleado);
                $entityManager->flush();
                return $this->redirectToRoute('index');
            }catch (\Exception $e){
                return new Response("Error eliminando el objeto");
            }
        }else
            return $this->render('ficha_contacto.html.twig', ['empleado' => null]);
    }

    #[Route('/empleado/buscar/{texto}', name: 'buscar_empleado')]
    public function buscar(ManagerRegistry $doctrine, $texto): Response
    {
        $repositorio = $doctrine->getRepository(Empleado::class);
        $empleados = $repositorio->findBySurname($texto);
        
        return $this->render('page/lista_empleados.html.twig', 
        ['empleados' => $empleados]);
    }

    #[Route('/lista-empleados', name: 'lista_empleados')]
    public function lista_empleados(ManagerRegistry $doctrine): Response
    {
        $empleadoRepo = $doctrine->getRepository(Empleado::Class);
        $empleados = $empleadoRepo->findAll();

        return $this->render('page/lista_empleados.html.twig', [
            'empleados' => $empleados
        ]
        );
    }
}
