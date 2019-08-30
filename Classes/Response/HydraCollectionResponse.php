<?php
declare(strict_types=1);

namespace SourceBroker\T3api\Response;

use JMS\Serializer\Annotation as Serializer;

/**
 * Class CollectionResponse
 */
class HydraCollectionResponse extends AbstractCollectionResponse
{
    /**
     * @return array
     * @Serializer\SerializedName("hydra:member")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"__hydra_collection_response"})
     */
    public function getMembers(): array
    {
        return parent::getMembers();
    }

    /**
     * @return int
     * @Serializer\SerializedName("hydra:totalItems")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"__hydra_collection_response"})
     */
    public function getTotalItems(): int
    {
        return parent::getTotalItems();
    }

    /**
     * @return array
     * @Serializer\SerializedName("hydra:view")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"__hydra_collection_response"})
     */
    public function getView(): array
    {
        $viewData = [];

        if ($this->operation->getApiResource()->getPagination()->isEnabled()) {
            $pagination = $this->operation->getApiResource()->getPagination();
            $lastPage = (int)ceil($this->getTotalItems() / $pagination->getNumberOfItemsPerPage());
            $viewData['hydra:first'] = $this->operation->getRoute()->getPath() . '?' .
                $this->getCurrentQueryStringWithOverrideParams([
                    $pagination->getPageParameterName() => 1,
                ]);
            $viewData['hydra:last'] = $this->operation->getRoute()->getPath() . '?' .
                $this->getCurrentQueryStringWithOverrideParams([
                    $pagination->getPageParameterName() => $lastPage,
                ]);

            if ($pagination->getPage() > 1) {
                $viewData['hydra:prev'] = $this->operation->getRoute()->getPath() . '?' .
                    $this->getCurrentQueryStringWithOverrideParams([
                        $pagination->getPageParameterName() => $pagination->getPage() - 1,
                    ]);
            }
            if ($pagination->getPage() < $lastPage) {
                $viewData['hydra:next'] = $this->operation->getRoute()->getPath() . '?' .
                    $this->getCurrentQueryStringWithOverrideParams([
                        $pagination->getPageParameterName() => $pagination->getPage() + 1,
                    ]);
            }
        }

        return $viewData;
    }

    /**
     * @return array
     * @Serializer\SerializedName("hydra:search")
     * @Serializer\VirtualProperty()
     * @Serializer\Groups({"__hydra_collection_response"})
     */
    public function getSearch(): array
    {
        $searchData = [];
        $searchData['hydra:template'] = $this->operation->getRoute()->getPath();
        $searchData['hydra:mapping'] = [];
        $variables = [];
        foreach ($this->operation->getFilters() as $filter) {
            $order = $filter->getArgument('orderParameterName');
            $variable = sprintf($order ? $order . '[%s]' : '%s', $filter->getProperty());
            if (!in_array($variable, $variables)) {
                $searchData['hydra:mapping'][] = [
                    'variable' => $variable,
                    'property' => $filter->getProperty()
                ];
                $variables[] = $variable;
            }
        }
        $searchData['hydra:template'] .= sprintf('{?%s}', implode(',', $variables));
        return $searchData;
    }

    /**
     * @param array $overrideParams
     *
     * @return string
     */
    protected function getCurrentQueryStringWithOverrideParams(array $overrideParams): string
    {
        parse_str($_SERVER['QUERY_STRING'], $qsParams);

        return http_build_query(array_merge($qsParams, $overrideParams));
    }
}
