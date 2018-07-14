<?php

namespace RecetteBundle\Controller;

use RecetteBundle\Entity\Recette;
use RecetteBundle\Services\FileManager;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Recette controller.
 *
 * @Route("dashboard/recettes")
 */
class RecetteController extends Controller
{

    /**
     * Lists all recette entities.
     *
     * @Route("/", name="recette_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $recettesvalide = $em->getRepository('RecetteBundle:Recette')->getValideRecette();
        $recettes_enattend = $em->getRepository('RecetteBundle:Recette')->getEnAttendRecette();;
        $recettes_refuse = $em->getRepository('RecetteBundle:Recette')->getRefuseRecette();;
        $n = sizeof($recettesvalide) + sizeof($recettes_enattend) + sizeof($recettes_refuse);


        return $this->render('recette/index.html.twig', array(
            'recettes_validees' => $recettesvalide,
            'recettes_enattends' => $recettes_enattend,
            'recettes_refusees' => $recettes_refuse,
            'n_recettes' => $n
        ));
    }

    /**
     * Creates a new recette entity.
     *
     * @Route("/new", name="recette_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        //L'instanciation de FileSysteme
        $fileSysteme = new Filesystem();
        // Get File Manager Service
        $file_manger = $this->get('file.manager');

        $uploadRootPath = $this->getParameter('files_directory');
        $username = $this->getUser()->getUsername();
        $userUploadRootPath = $uploadRootPath.$username.'/';

        // Vérification de l'existance du dossier Uploads
        $file_manger->pathExsitence($fileSysteme, $userUploadRootPath);

        //Création du Form
        $recette = new Recette();
        $form = $this->createForm('RecetteBundle\Form\RecetteType', $recette);

        $form->handleRequest($request);



        if ($form->isSubmitted() && $form->isValid()) {


            //upload the file
            /**
             * @var UploadedFile $profil_file
             */


            $recetteUploadPath = $userUploadRootPath.$recette->getTitre().'/';

            $file_manger->pathExsitence($fileSysteme, $recetteUploadPath);

            $image_file = $recette->getImage();

            $imagefilename = $file_manger->uploadFile($image_file, $recetteUploadPath);

            $etapes = $recette->getEtapes();

        // Set Etape
            if(sizeof($etapes) !== 0) {

                foreach($etapes as $etape) {
                    $etape_image_File = $etape->getEtapeImage();
                    $etapeImagefilename = $file_manger->uploadFile($etape_image_File, $recetteUploadPath.'etapes');
                    $etape->setEtapeImage($etapeImagefilename);
                    $etape->setRecette($recette);
                }
            }

            $em = $this->getDoctrine()->getManager();

            $recette->setUser($this->getUser());
            $recette->setImage($imagefilename);
            $recette->setProvince($form['province']->getData());
            $recette->setDateCreation(new \DateTime());

            $em->persist($recette);
            $em->flush();

            return $this->redirectToRoute('recette_index');
        }



        return $this->render('recette/new.html.twig', array(
            'recette' => $recette,
            //'formfieldNames' => $formfieldNames,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a recette entity.
     *
     * @Route("/{id}", name="recette_show")
     * @Method("GET")
     */
    public function showAction(Recette $recette)
    {
        $deleteForm = $this->createDeleteForm($recette);

        return $this->render('recette/show.html.twig', array(
            'recette' => $recette,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing recette entity.
     *
     * @Route("/{id}/edit", name="recette_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Recette $recette)
    {
        $deleteForm = $this->createDeleteForm($recette);
        $editForm = $this->createForm('RecetteBundle\Form\RecetteType', $recette);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('recette_edit', array('id' => $recette->getId()));
        }

        return $this->render('recette/edit.html.twig', array(
            'recette' => $recette,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a recette entity.
     *
     * @Route("/{id}", name="recette_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Recette $recette)
    {
        $form = $this->createDeleteForm($recette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($recette);
            $em->flush();
        }

        return $this->redirectToRoute('recette_index');
    }

    /**
     * Creates a form to delete a recette entity.
     *
     * @param Recette $recette The recette entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Recette $recette)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('recette_delete', array('id' => $recette->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }


    private function getChildrenNames($form_views) {

        $childrens = $form_views->all();
        $childrenNames = [];
        foreach($childrens as $child) {
            array_push($childrenNames,$child->getName());
        }


        return $childrenNames;
    }
}
