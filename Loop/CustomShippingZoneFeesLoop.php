<?php

namespace CustomShippingZoneFees\Loop;


use CustomShippingZoneFees\Model\CustomShippingZoneFees;
use CustomShippingZoneFees\Model\CustomShippingZoneFeesModules;
use CustomShippingZoneFees\Model\CustomShippingZoneFeesModulesQuery;
use CustomShippingZoneFees\Model\CustomShippingZoneFeesQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

class CustomShippingZoneFeesLoop extends BaseLoop implements PropelSearchLoopInterface
{

    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument('id'),
            Argument::createIntListTypeArgument('module_id'),
            Argument::createIntListTypeArgument('without_zone'),
            Argument::createAlphaNumStringTypeArgument('locale')
        );
    }

    public function buildModelCriteria()
    {
        $query = CustomShippingZoneFeesQuery::create();

        if ($ids = $this->getId()){
            $query->filterById($ids);
        }

        if ($moduleId = $this->getmoduleId()){
            $query
                ->useCustomShippingZoneFeesModulesQuery()
                ->filterByModuleId($moduleId)
                ->endUse();
        }

        if ($withoutZone = $this->getWithoutZone()){
            $moduleShippingZone = CustomShippingZoneFeesModulesQuery::create()->filterByModuleId($withoutZone)->find();
            $ids = null;
            /** @var CustomShippingZoneFeesModules $m */
            foreach ($moduleShippingZone as $m){
                $ids[] = $m->getCustomShippingZoneFeesId();
            }
            $query->filterById( $ids,Criteria::NOT_IN);
        }

        return $query;
    }

    /**
     * @param LoopResult $loopResult
     * @return LoopResult
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function parseResults(LoopResult $loopResult)
    {
        $locale = $this->getLocale();
        /** @var CustomShippingZoneFees $shippingZone */
        foreach ($loopResult->getResultDataCollection() as $shippingZone) {
            $loopResultRow = new LoopResultRow($shippingZone);
            $loopResultRow
                ->set("ID", $shippingZone->getId())
                ->set("FEE", $shippingZone->getFee())
                ->set("NAME", $shippingZone->setLocale($locale)->getName())
                ->set("DESCRIPTION", $shippingZone->setLocale($locale)->getDescription())
                ->set("COUNTRY_ID", $shippingZone->getCountryId())
                ->set("COUNTRY_NAME", $shippingZone->getCountry()->setLocale($locale)->getTitle())
                ->set("ZIP_CODES", $shippingZone->getZipcodes())
            ;
            $loopResult->addRow($loopResultRow);
        }
        return $loopResult;
    }
}