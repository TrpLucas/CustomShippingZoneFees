<?php
/**
 * Created by PhpStorm.
 * User: nicolasbarbey
 * Date: 21/08/2020
 * Time: 15:59
 */

namespace CustomShippingZoneFees\Controller;


use CustomShippingZoneFees\Form\CustomShippingZoneFeesCreateForm;
use CustomShippingZoneFees\Form\ZipCodeCreateForm;
use CustomShippingZoneFees\Model\CustomShippingZoneFees;
use CustomShippingZoneFees\Model\CustomShippingZoneFeesQuery;
use CustomShippingZoneFees\Model\CustomShippingZoneFeesZip;
use CustomShippingZoneFees\Model\CustomShippingZoneFeesZipQuery;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Model\Base\CurrencyQuery;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Tools\URL;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CustomShippingZoneFeesController
 * @Route("/admin/module/CustomShippingZoneFees", name="custom_shipping_zone") 
 */
class CustomShippingZoneFeesController extends BaseAdminController
{
    /**
     * @Route("/create", name="create") 
     */
    public function createShippingZoneAction()
    {
        $langs = LangQuery::create()->filterByActive(1)->find();

        $createForm = $this->createForm(CustomShippingZoneFeesCreateForm::getName());
        try{
            $form = $this->validateForm($createForm);

            $shippingZone = new CustomShippingZoneFees();

            foreach ($langs as $lang){
                $shippingZone
                    ->setFee($form->get('fee')->getData())
                    ->setLocale($lang->getLocale())
                    ->setName($form->get('name')->getData())
                    ->setDescription($form->get('description')->getData());
            }
            $shippingZone->save();

            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees"));

        }catch (\Exception $exception){
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees",[
                "err" => $exception->getMessage()
            ]));
        }
    }
    /**
     * @Route("/delete/{id}", name="delete") 
     */
    public function deleteShippingZoneAction($id)
    {
        try{
            $shippingZone = CustomShippingZoneFeesQuery::create()->findOneById($id);
            foreach ($shippingZone->getCustomShippingZoneFeesZips() as $zip){
                $zip->delete();
            }
            $shippingZone->delete();
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees"));
        }catch (\Exception $exception) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees", [
                "err" => $exception->getMessage()
            ]));
        }
    }

    /**
     * @Route("/update/{id}", name="update") 
     */
    public function updateShippingZoneAction($id, Request $request)
    {
        $shippingZone = CustomShippingZoneFeesQuery::create()->findOneById($id);
        /** @var Lang $lang */
        $lang = $request->getSession()->get("thelia.admin.edition.lang");
        $createForm = $this->createForm(CustomShippingZoneFeesCreateForm::getName());
        try{
            $form = $this->validateForm($createForm);

            $shippingZone
                ->setFee($form->get('fee')->getData())
                ->setLocale($lang->getLocale())
                ->setName($form->get('name')->getData())
                ->setDescription($form->get('description')->getData())
                ->save();

            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees/edit/$id", [
                "edit_language_id" => $lang->getId()
            ]));
        }catch (\Exception $exception){
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees/edit/$id", [
                "err" => $exception->getMessage(),
                "edit_language_id" => $lang->getId()
            ]));
        }
    }

    /**
     * @Route("/zip/create/{id}", name="zip_create") 
     */
    public function createZipShippingZoneAction($id, Request $request)
    {
        $lang = $request->getSession()->get("thelia.admin.edition.lang");
        $createForm = $this->createForm(ZipCodeCreateForm::getName());
        try{
            $shippingZone = CustomShippingZoneFeesQuery::create()->findOneById($id);
            $form = $this->validateForm($createForm);
            $zip = (new CustomShippingZoneFeesZip())
                ->setZipCode($form->get("zip")->getData())
                ->setCountryId($form->get("country")->getData());
            $shippingZone->addCustomShippingZoneFeesZip($zip)->save();
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees/edit/$id",[
                "edit_language_id" => $lang->getId()
            ]));
        }catch (\Exception $exception){
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees/edit/$id", [
                "err" => $exception->getMessage(),
                "edit_language_id" => $lang->getId()
            ]));
        }
    }

    /**
     * @Route("/zip/delete/{id}", name="zip_delete") 
     */
    public function deleteZipShippingZoneAction($id, Request $request)
    {
        $lang = $request->getSession()->get("thelia.admin.edition.lang");
        $zip = CustomShippingZoneFeesZipQuery::create()->findOneById($id);
        $customShippingZoneFeesId = $zip->getCustomShippingZoneFeesId();
        try{
            $zip->delete();
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees/edit/$customShippingZoneFeesId",[
                "edit_language_id" => $lang->getId()
            ]));
        }catch (\Exception $exception) {
            return $this->generateRedirect(URL::getInstance()->absoluteUrl("/admin/module/CustomShippingZoneFees/edit/$customShippingZoneFeesId", [
                "err" => $exception->getMessage(),
                "edit_language_id" => $lang->getId()
            ]));
        }
    }

    /**
     * @return \Thelia\Core\HttpFoundation\Response
     * @throws \Propel\Runtime\Exception\PropelException
     * @Route("/edit/{id}", name="edit") 
     */
    public function renderShippingZonePageAction($id, Request $request)
    {
        $defaultLang = LangQuery::create()->findOneByByDefault(1);
        $locale = $defaultLang->getLocale();
        if ($langId = $request->get('edit_language_id')){
            $locale = LangQuery::create()->findOneById($langId)->getLocale();
        }
        $shippingZone = CustomShippingZoneFeesQuery::create()->findOneById($id);

        $zipCodes = $shippingZone->getCustomShippingZoneFeesZips();

        $defaultCurrency = CurrencyQuery::create()->filterByByDefault(1)->findOne();
        $currencies = CurrencyQuery::create()->filterByVisible(1)->filterByByDefault(0)->find()->toArray();

        return $this->render('CustomShippingZoneFeesEdit', [
            'shippingZoneId' => $shippingZone->getId(),
            'edit_language_id' => $langId ? : $defaultLang->getId(),
            'defaultCurrency' => $defaultCurrency,
            'currencies' => $currencies,
            "err" => $request->get('err')
        ], 200);
    }
}

