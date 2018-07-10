# How to add a KPI block in modern pages?

A KPI block (also called KPI row) is shown here: https://prnt.sc/k4ofgi

You can follow these steps to easily add a KPI row to a modern page :
* define your KPI classes:
  * you can use one of existing KPI classes, from `PrestaShop\PrestaShop\Adapter\Kpi` namespace,
  * you can create new classes - they must implement the `PrestaShop\PrestaShop\Core\Kpi\KpiInterface`
* define a KPI row factory service in `src/PrestaShopBundle/Resources/config/services/core/kpi.yml`

    Example from translations page:
    ```yaml
    prestashop.core.kpi_row.factory.translations_page:
        class: PrestaShop\PrestaShop\Core\Kpi\Row\KpiRowFactory
        arguments:
            - '@prestashop.adapter.kpi.enabled_languages'
            - '@prestashop.adapter.kpi.main_country'
            - '@prestashop.adapter.kpi.translations'
    ```
    Note: the KPI row factory accepts unlimited number of arguments and each argument is a KPI, that will be built into a KPI row.

* Build the KPI row in your controller's action and assign it to twig by returning it:
    ```php
    public function showSettingsAction(Request $request)
    {
        $legacyController = $request->attributes->get('_legacy_controller');
        
        // Create the KPI row factory service
        $kpiRowFactory = $this->get('prestashop.core.kpi_row.factory.translations_page');

        return [
            'layoutTitle' => $this->trans('Translations', 'Admin.Navigation.Menu'),
            'enableSidebar' => true,
            'help_link' => $this->generateSidebarLink($legacyController),
            
            // Assign the built KPI row to twig
            'kpiRow' => $kpiRowFactory->build(),
        ];
    }
    ```

* The final step is to render the KPI row in your twig template, using `renderKpiRow` method from `CommonController` and passing it the previously assigned `kpiRow` variable:
    ```twig
    {% block translations_kpis_row %}
        <div class="row">
            {{ render(controller(
                'PrestaShopBundle:Admin\\Common:renderKpiRow',
                { 'kpiRow': kpiRow }
            )) }}
        </div>
    {% endblock %}
    ```