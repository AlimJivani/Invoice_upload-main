<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use App\Form\ImportCsvFileType;
use App\Entity\FileData;
use App\Repository\FileDataRepository;
use Symfony\Component\Form\FormError;

class CustomerController extends AbstractController
{
    #[Route('/', name: 'customer')]
    public function index(Request $request, FileDataRepository $fileDataRepository): Response
    {

        $days = $this->getParameter('COEFFICIENT_DAYS');  
        $maxCoefficient = $this->getParameter('MAX_COEFFICIENT');
        $minCoefficient = $this->getParameter('MIN_COEFFICIENT');
        $messageSuccess = false;
        
        $entityManager = $this->getDoctrine()->getManager();
        $form = $this->createForm(ImportCsvFileType::class);
        $form->handleRequest($request);

        
        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFile = $form['csvFile']->getData();
            
            $file = fopen($uploadedFile, 'r');

            
             // Check valid file extension, because symfony validator consider .csv as plain text file
            $isValidFile = true;
            if($uploadedFile->getClientOriginalExtension() != 'csv'){
                $form['csvFile']->addError(new FormError('Please upload a valid CSV document'));
                $isValidFile = false;
            }
            
            $lineCount = $successCount = $failedCount =  0;
            while (($line = fgetcsv($file)) !== FALSE && $isValidFile )  {

                $lineCount += 1 ; 
                if(!$line[0]){
                    continue;
                }

                if (count(array_filter($line)) == 3) {
                    $currentDate = date_create(date('Y-m-d'));
                    $dueDate = date_create($line[2]);
                    $amount = $line[1];
                    $differentDate = date_diff($currentDate,$dueDate);
                    $differentDate = $differentDate->days;

                    $fileData = $fileDataRepository->findUserByInvoiceId($line[0]);
                    
                    $sellingPrice = ($differentDate > $days) ? ($amount * $maxCoefficient) : ($amount * $minCoefficient);

                    if (!$fileData) {
                        $fileData = new FileData();
                    } 

                    $fileData->setInvoiceId($line[0]);
                    $fileData->setAmount($amount);
                    $fileData->setDueOn($dueDate);
                    $fileData->setSellingPrice($sellingPrice);

                    $entityManager->persist($fileData);
                    $entityManager->flush();
                    unset($fileData);
                    $messageSuccess = true;
                    $successCount++;

                } else {
                    $failedCount++;
                    $form['csvFile']->addError(new FormError('In line number '.$lineCount.' Data missing'));
                    continue;
                    $this->addFlash(
                        'falied',
                        'Falied!!!, Your file has not been saved!'
                    );
                }     
            }
            fclose($file);
        }

        // flash message 
        if($messageSuccess){
            $message = "<p>Your file has been saved!<br> Total $successCount record(s) are added into system.</p>";
            if ($failedCount) {
                $message .= "<p style='color:red;'>Total $failedCount record(s) are failed to import.</p>";
            }
            $this->addFlash(
                "success",
                $message
            );
        }
        
        return $this->render('customer/customer.html.twig', [
            'importCsvForm' => $form->createView(),
        ]);
    }

    #[Route('/listingdata', name: 'listingdata')]
    public function listingData( FileDataRepository $fileDataRepository)
    {
        $data = $fileDataRepository->findAll();

        return $this->render('data/data.html.twig', [
            'data' => $data,
        ]);
    }
    
}
