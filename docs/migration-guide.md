# Migration guide of Back Office page to Symfony 3

In order to migrate a legacy page, we need to migrate 3 parts of the application: the templates, the forms and the controllers which contains the business logic in PrestaShop.

## Strategy/TodoList

This is the list of items we usually have to solve in order to complete the migration of an old controller.

- [ ] Creations
  - [ ] Create `PrestaShopBundle/Controller/<path>/<Your>Controller`
  - [ ] Create related actions (functions matched to URIs)
  - [ ] Declare routing in `config/admin/routing_*.yml` file
  - [ ] Create Symfony form types for each form available in pages
  - [ ] Create and configure Javascript (using Webpack/ES6) file
  - [ ] Create every twig blocks in `views/<path>/*.html.twig`
  - [ ] Implement Forms submission
  - [ ] Implement Forms validation
  - [ ] If required, implement (request) Parameters update
  - [ ] Check Error Handling
  - [ ] Checks permissions and demo mode constraints
  - [ ] Re-introduce hooks (and document the missing one if you can't for a good reason)
  - [ ] Complete `Link` class to map PrestaShop menu to the new page
- [ ] Deletions
  - [ ] Remove the old controller in `controllers/admin/Admin*.php`
  - [ ] Remove related old templates (in `admin-dev/themes/default/template/controllers/*`)

## Templating with Twig

This is mostly the easy part. Legacy pages use Smarty when modern pages use Twig, theses templating engines are similar in so many ways.

For instance, this is a legacy template (all of them are located in `admin-dev/themes/default/template/controller`) folder:

```html
<span class="employee_avatar_small">
    <img class="img" alt="" src="{$employee_image}" />
</span>
{$employee_name}
```

and his (probable) migration to Twig:

```twig
<span class="employee_avatar_small">
    <img class="img" alt="{{ employee.name }}" src="{{ employee.image }}" />
</span>
{{ employee.name }}
```

Syntaxes are really similar, and we have ported every helper from Smarty to Twig:

| Smarty                                 | Twig                                                      |
|----------------------------------------|-----------------------------------------------------------|
| { l s='foo' d='domain' }               | {{ 'foo'\|trans({}, 'domain') }}                          |
| { hook h='hookName }                   | {{ renderhook('hookName') }}                              |
| {$link->getAdminLink('AdminAccess')}   | {{ getAdminLink('LegacyControllerName') }}                |

Macros/functions are specific to the modern pages to help with recurrent blocks:

* `form_label_tooltip(name, tooltip, placement)`: render a form label (by his name) with information in roll hover
* `check(variable)`: check if a variable is defined and not empty
* `tooltip(text, icon, position)`: render a tooltip with information in roll hover (doesn't render a label)
* `infotip(text)`, `warningtip(text)`: render information and warning tip (more like alert messages)
* `label_with_help(label, help)`: render a label with information in roll hover (render a label)

Finally, legacy templates use [Bootstrap 3](https://getbootstrap.com/docs/3.3/) when modern pages use the [PrestaShop UI Kit](http://build.prestashop.com/prestashop-ui-kit/) that relies on [Bootstrap 4](https://getbootstrap.com/docs/4.0/getting-started/introduction/), so you'll need to update some markup, especially CSS classes accordingly.

# Forms

## Legacy forms management

Forms are the biggest part of the migration. Before we have form helpers that mostly generate, validate and handle all the things when in Symfony every step (creation, validation and request handling) needs to be done by the developer.

For instance, this is code that you can find into a Legacy Controller:

```php
$this->fields_options = array(
    'general' => array(
        'title' => $this->trans('Logs by email', array(), 'Admin.Advparameters.Feature'),
        'icon' => 'icon-envelope',
        'fields' => array(
            'PS_LOGS_BY_EMAIL' => array(
                'title' => $this->trans('Minimum severity level', array(), 'Admin.Advparameters.Feature'),
                'hint' => $this->trans('Enter "5" if you do not want to receive any emails.', array(), 'Admin.Advparameters.Help'),
                'cast' => 'intval',
                'type' => 'text',
            ),
        ),
        'submit' => array('title' => $this->trans('Save', array(), 'Admin.Actions')),
    ),
);
```

This is how this configuration is rendered by the legacy controller (without anything to write in templates):

![Logs by email form](https://i.imgur.com/hAziI9Y.png)

The block is rendered and mapped to the controller url, the form is validated and mapped to the `PS_LOGS_BY_EMAIL` configuration key and automatically persisted in database, the label have a *hint* message in roll hover.

Let's see how we can do that in the modern pages.

## Modern form management

In modern pages and with Symfony, the form management is really decoupled from Controllers and you need to create your forms, to validate them, to map them to the current HTTP request and persist your data yourself. You also need to create your form templates (but we have a nice form theme already provided that helps you a lot with it).

### Form creation

Creation of forms using Symfony is already [documented](http://symfony.com/doc/current/forms.html) in their documentation.
You need to create your form types in `src/PrestaShopBundle/Form/Admin/{Page}/` folder, you can rely on existing forms to create your owns but at this moment there is nothing really specific to the PrestaShop integration.

Some Form types are subtypes to help you integrate the specific form inputs we use in the Back Office, you'll find them inside the *Types* folders:

* `ChoiceCategoryTreeType`
* `CustomMoneyType`
* `DatePickerType`
* `TextWithUnitType`
* ...

Most of the time, there are the Symfony integration of inputs defined in the PrestaShop UI Kit.
> Before create a new form input type, check first in this folder if the input exists.

Now a form is created and declared [as a service](http://symfony.com/doc/current/form/form_dependencies.html#define-your-form-as-a-service) you can use it inside your Controllers (we'll see it in the **Controllers** section of this guide).

### Form data providers

To manage existing data and save the data coming from user (submitting the form for instance), you need to create and register a Form Data provider.
You can rely on already existing implementations, or on the interface:

```php
interface FormDataProviderInterface
{
    /**
     * @return array the form data as an associative array
     */
    public function getData();

    /**
     * Persists form Data in Database and Filesystem.
     *
     * @param array $data
     * @return array $errors if data can't persisted an array of errors messages
     * @throws UndefinedOptionsException
     */
    public function setData(array $data);
}
```

The idea is to uncouple the data management from Controller, so populating current data and set new data will be done in theses implementations. Be careful, we are not persisting anything here.

### Form data handlers

Once you are able to manage data that comes from or should be sent by forms, you need a way to build your forms (they can be themselves composed of multiple forms) and to persist the data in filesystem or database. You need to create and register a Form data handler.
You can rely on already existing implementations, or on the interface:

```php
interface FormHandlerInterface
{
    /**
     * @return FormInterface
     */
    public function getForm();

    /**
     * Describe what need to be done on saving the form: mostly persists the data
     * using a form data provider, but it's also the right place to dispatch events/log something.
     *
     * @param array $data data retrieved from form that need to be persisted in database
     * @throws \Exception if the data can't be handled
     *
     * @return void
     */
    public function save(array $data);
}
```

> In some cases, you may want to rely on **$formDataProvider->setData()** directly, this behavior must be avoided.

### Form request handling in Controllers

In modern pages, Controllers have or should have only one responsability: handle the User request and return a response. This is why in modern pages, controllers should be as thin as possible and rely on specific classes (services) to manage the data. As always, you can rely on already existing implementations, like in the [PerformanceController](https://github.com/PrestaShop/PrestaShop/blob/develop/src/PrestaShopBundle/Controller/Admin/AdvancedParameters/PerformanceController.php).

This is how we manage a form inside a Controller:

```php
$form = $this->get('prestashop.adapter.performance.form_handler')->getForm();
$form->handleRequest($request);
/* ... some authorizations checks */
if ($form->isSubmitted()) {
    $data = $form->getData();
    $saveErrors = $this->get('prestashop.adapter.performance.form_handler')->save($data);
    if (0 === count($saveErrors)) {
        $this->addFlash('success', $this->trans('Successful update.', 'Admin.Notifications.Success'));
        return $this->redirectToRoute('admin_performance');
    }
    $this->flashErrors($saveErrors);
}
return $this->redirectToRoute('admin_performance');
}
```

So, basically three steps:

* Get information from User request and get form data;
* If form has been submitted, validate the form;
* If form is valid, save it. Else, return form errors and redirect.

> Every form in modern controllers must be handled this way, and the controller code should be kept minimalist but easier to read and to be understood.

### Render the form view, Twig templating

The rendering of forms in Twig is already [described](https://symfony.com/doc/current/form/rendering.html) in Symfony documentation. We use our own [Form theme](https://github.com/PrestaShop/PrestaShop/blob/develop/src/PrestaShopBundle/Resources/views/Admin/TwigTemplateForm/prestashop_ui_kit.html.twig) that contains specific input and markup for PrestaShop UI Kit, you can see it as a customized version of Bootstrap 4 form theme of Symfony 3, though we don't rely on it directly right now.

To sum up how it works, the controller send an instance of `FormView` to Twig and Twig have form helpers to render the right markups for every types of fields (because each Form Type have an associated markup described in the Form theme):

```twig
    {{ form_start(logsByEmailForm) }}
    <div class="col-md-12">
      <div class="col">
        <div class="card">
          <h3 class="card-header">
            <i class="material-icons">business_center</i> {{ 'Logs by email'|trans }}
          </h3>
          <div class="card-block">
            <div class="card-text">
              <div class="form-group row">
              {{ ps.label_with_help(('Minimum severity level'|trans), ('Enter "5" if you do not want to receive any emails.'|trans({}, 'Admin.Advparameters.Feature')), 'col-sm-2') }}
                <div class="col-sm-8">
                  {{ form_errors(logsByEmailForm.severity_level) }}
                  {{ form_widget(logsByEmailForm.severity_level) }}
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <button class="btn btn-primary">{{ 'Save'|trans({}, 'Admin.Actions') }}</button>
          </div>
        </div>
      </div>
    </div>
    {{ form_end(logsByEmailForm) }}
```
All theses helpers are documented and help you to generate an HTML form from your `FormView` object, with the right markup to be rendered by the PrestaShop UI Kit. As for now, a lot of forms have already been migrated and rendered so you can rely and improve existing implementations.

Every templates from modern pages can be found inside `src/PrestaShopBundle/Resources/views/Admin` folder. Be careful, the organization of this templates [is about to change](https://github.com/PrestaShop/PrestaShop/pull/8489) soon (in 1.7.4) so try to keep, maintain or improve the organization.

Basically, we try to order template by page and domains, keep in mind each part of template can be overriden by PrestaShop developers using modules so use templates and Twig blocks wisely to make their job easy.

## Controller/Routing

### Modern/Symfony Controllers

> As always, you'll find all documentation you may need in Symfony documentation about [Controllers](https://symfony.com/doc/current/controller.html) and [Routing](https://symfony.com/doc/current/routing.html).

For every page we have to migrate we need to create one or more Controller: if you think a Legacy Controller need to be splitted into multiple controllers (good sign: differents urls locations), it's the right time to do it.

Every controller is created into `src/PrestaShopBundle/Controller/Admin` namespace. Since 1.7.3, we try to re-organize how theses controllers are created and we try to follow the menu from Back Office. For instance, if you are migrating a page located into "Advanced Parameters" section, put it into `src/PrestaShop/Controller/Admin/Configure/AdvancedParameters`. 
Same applies to **Improve** and **Sell** sections.

This is what we want to have in the end:

```
Controller/
└── Admin
    ├── Configure
    │   ├── AdvancedParameters
    │   └── ShopParameters
    ├── Improve
    │   ├── Design
    │   ├── International
    │   ├── Modules
    │   ├── Payment
    │   └── Shipping
    └── Sell
        ├── Catalog
        ├── Customers
        ├── CustomerService
        ├── Orders
        └── Stats
```

> Note: as Controllers are not available for override and can be regarded as internal classes, we don't consider moving a Controller in another namespace as a break of compatibility.

Symfony Controllers should be thin by default and have only one responsability: get the HTTP Request from user and return an HTTP Response. This means that every business logic should be done outside from Controller in dedicated classes:

* Form management
* Database access
* Validation
* etc...

You can take a look at [PerformanceController](https://github.com/PrestaShop/PrestaShop/blob/develop/src/PrestaShopBundle/Controller/Admin/AdvancedParameters/PerformanceController.php) for a good implementation, but at [ProductController](https://github.com/PrestaShop/PrestaShop/blob/develop/src/PrestaShopBundle/Controller/Admin/ProductController.php) for something you should avoid at all costs.

Once the Controller is created, it should contains "Actions". Actions are methods of Controllers (also called Controllers sometimes) mapped to a route, and with the responsability of returning a Response. You may avoid to create another functions, this probably means you should extract this code into external classes.

Regarding the rendering of a Response, there is some data specific to PrestaShop (in Back Office) that we must set to every action:

| Attribute                   |  Type                          |  Description                                            |
|-----------------------------|--------------------------------|---------------------------------------------------------|
| `layoutHeaderToolbarBtn`    | [['href', 'des','icon'], ...]  | Set buttons in toolbar on top of the page               |
| `layoutTitle`               | string                         | Main title of the page                                  |
| `requireAddonsSearch`       | boolean                        | If *true*, display addons recommendations button        |
| `requireBulkActions`        | boolean                        | If *true*, display bulk actions button                  |
| `showContentHeader`         | boolean                        | If *true*, display the page header                      |
| `enableSidebar`             | boolean                        | If *true*, display a sidebar                            |
| `help_link`                 | string                         | Set the url of "Help" button                            |
| `requireFilterStatus`       | boolean                        | ??? (Specific to Modules page?)                         |
| `level`                     | integer                        | Level of authorization for actions (Specific to modules)|

#### Helpers

Some helpers are specific to PrestaShop to help you manage the security and the dispatching of legacy hooks, all of them are directly available in Controllers that extends `FrameworkBundleAdminController`.

* `isDemoModeEnabled()`: some actions should not be allowed in Demonstration Mode
* `getDemoErrorMessage()`: returns a specific error message
* `addFlash(type, msg)`: accepts "success|error" and a message that will be display after redirection of the page
* `flashErrors([msgs])`: if you need to "flash" a collection of errors
* `dispatchHook(hookName, [params])`: some legacy hooks need to be dispatched to preserve backward compatibility
* `authorizationLevel(controllerName)`: check if you are allowed - as connected user - to do the related actions
* `langToLocale($lang)`: get the locale from a PrestaShop lang
* `trans(key, domain, [params])`: translate a string

### Manage the Security

In modern pages, the permissions system that check if the user is allowed to do CRUD actions have been improved.

Most of the time, you want to restrict accesses to some actions (like CREATE, READ, UPDATE, DELETE) for a specific model (like "Product", "User"). In PrestaShop Back Office, most of the resources are managed by only one Controller.

So if a logged user want to manage a resource he needs to have rights to access to this controller. Sounds idiot to tell it, but for instance, to access to "Product Catalog" page you need to have READ accesses, in order to display products information. If you want to delete a product you need DELETE accesses.

In a Controller for a specific Action, this is how you can restrict the accesses to specific autorizations:

```php
    use PrestaShopBundle\Security\Annotation\AdminSecurity;

    /**
     * @AdminSecurity("is_granted(['read','update', 'create','delete'], request.get('_legacy_controller')~'_')",
     *     message="You do not have permission to update this.",
     *     redirectRoute="foo_bar"
     * )
     *
     */
    public function fooAction(Request $request) { return new Response();}
```

#### What we have done here?

Using the annotation will check if the logged user is granted to access the Action (ie the url).
The annotation `AdminSecurity` have 5 properties:

The first one is an expression evaluated that must return a boolean, here we're checking if the user have all the rights on the Controller. Accesses on PrestaShop are link to the action (Create, ...) and the related controller (ADMINPREFERENCES, ...).

The second one - `message` - (optional) to configure the error message displayed to the user, if not allowed to access the action.

The third one - `redirectRoute` - (optional) to configure which route name the router will use to redirect you if not allow to access the action.

The fourth one - `domain` - (optional) to set the translation domain name of the message. 

The fifth one - `url` - (optional) should not be used. It's used to configure an url for redirection and not rely on the router.

> Once the Dashboard page will be migrated to Symfony, `url` property won't be used anymore.

> You shouldn't use both `url` and `redirectRoute` at the same time, but if you do, `redirectRoute` wins!

#### Manage the Demonstration Mode

PrestaShop is provided with a Demonstration Mode that give logged users some rights and restrictions, no matter
the real rights they may have to the application. To be more clear about it, this define rights at application level and not at the user level on the resources and actions of the application for the logged user.

> The demonstration mode can be enabled and tested by switching the value of `_PS_MODE_DEMO_` to `true` in `config/defines.inc.php`.

When an action have specific restrictions in Demonstration Mode, you can use the `DemoRestricted` annotation:

```php
    use PrestaShopBundle\Security\Annotation\DemoRestricted;

    /**
     * @DemoRestricted("route_to_be_redirected",
     *     message="You can't do this when demo mode is enabled.",
     *     domain="Admin.Global"
     * )
     *
     */
    public function fooAction(Request $request) { return new Response();}
```

> `message` and `domain` are both optional.

#### And if I want to restrict for a specific part of my Controller?

Sometimes, the restrictions depends on results of user input or an action manage both the display and the update of a resource. What if we want to allow READ action but not the UPDATE?

In this case, you can rely on functions available in Controllers Helpers we have described before: `isDemoModeEnabled` and `authorizationLevel` functions.

### Routing in PrestaShop

In order to map an Action to an url, we need to register a route and update a legacy class called `Link`.
Routes are declared in `src/PrestaShopBundle/Resources/config/admin` folder, using a `routing_{domain}.yml` file and imported in `routing_admin.yml` file.

Nothing special here except that you *must* declare a property called `_legacy_controller` with the old name of controller you are migrating in order to make the class `Link` aware of it: this class is reponsible of generating urls in the legacy parts of PrestaShop.

Let's see what we have done when we have migrated the "System Information" page inside the "Configure >Advanced Parameters" section:

```yaml
admin_system_information:
    path: system_information
    methods: [GET]
    defaults:
        _controller: 'PrestaShopBundle\Controller\Admin\AdvancedParameters\SystemInformationController::indexAction'
        _legacy_controller: AdminInformation
```

> We have decided to use YAML for services declaration and routing, don't use annotations please!

And now the update of `Link` class:

```php
// classes/Link.php, in getAdminLink()
case 'AdminInformation':
                $sfRoute = array_key_exists('route', $sfRouteParams) ? $sfRouteParams['route'] : 'admin_system_information';

                return $sfRouter->generate($sfRoute, $sfRouteParams, UrlGeneratorInterface::ABSOLUTE_URL);
```

And now, every link to "System Information" page in legacy parts will point to the new url.

> Be careful, some urls are hardcoded in legacy! Make a search using an IDE like PHPStorm and use the Link class when needed in Controllers, "{$url->link->getAdminLink()}" in smarty or "{{ getAdminLink() }}" in Twig.

# How to migrate hooks?

Hooks are the most important feature for the PrestaShop developers because they allow them to improve PrestaShop by adding code or content in multiple points of the application. For Symfony developers, you can see that as Events on steroïds.
To keep some degree of compatibility with 1.6 or 1.7 (pre-Symfony migration) modules, we need to ensure that hooks are still availables and called and/or rendered at the right place.

To get the list of available Hooks in modern pages it's really easy. Thanks to the hook profiler introduced in `1.7.3`, you get the list of available hooks with a lot of information. Sadly, legacy system don't have any way to get the list of hooks dispatched for a page.

This is how you can get the list:

In ``classes/Hook:exec`` [function](https://github.com/PrestaShop/PrestaShop/blob/develop/classes/Hook.php#L733) add the following code:

```php
file_put_contents('hooks.txt', PHP_EOL. $hook_name, FILE_APPEND | LOCK_EX);
```

> Note that only hooks that are prefixed by "display" are rendered to a page, for the others ones in the modern pages you can register the hook and use `dump()` function and check if the dump() call have been registered in profiler.

And then, access the url of the page you want to migrate. In ``admin-dev/hooks.txt``, you'll see the list of available hooks in the legacy page. Now, create a module that hook on each of these hooks and render something visible that you can retrieve in the new page.

This is an example with the Logs page (still in WIP as of 12/12/2017):

#### In legacy page
![hooks registration legacy](https://i.imgur.com/IEpM99F.png)
####  In modern page
![hooks registration modern](https://i.imgur.com/KF07ydt.png)

## Dispatch a hook in a modern Controller

You can do it using the function `dispatchHook($name, array $parameters)`:

```php
$this->dispatchHook('actionAdminPerformanceControllerPostProcessBefore', array('controller' => $this));
```

## Dispatch a hook in a specific class

You need to inject [HookDispatcher](https://github.com/PrestaShop/PrestaShop/blob/71ce2abf883c3d47e24e0aa07d461afb913d0511/src/PrestaShopBundle/Service/Hook/HookDispatcher.php) class, or you'd better use the service `prestashop.hook.dispatcher` if this class is already used as a Symfony service:

```php
use PrestaShopBundle\Service\Hook\HookEvent;
use PrestaShopBundle\Service\Hook\HookDispatcher;

$hookEvent = new HookEvent();
$hookEvent->setHookParameters($parameters);
$this->hookDispacher->dispatch($eventName, $hookEvent);
```

> Under the hood, we use an instance of Symfony `EventDispatcher`.

## Dispatch/Render a hook in Twig templates

Some hooks are directly rendered in templates, because PrestaShop developers want to add/remove information from blocks. Of course you can do it using template override but you may lose compatibility if templates are updated in latest versions of PrestaShop.

```twig
{{ renderhook('hookName', {
    'param1': 'value1',
    'param2': 'value2'
}) }}
```

# Deletions

Now everything is migrated, refactored, extracted to specific classes and works like a charm, it's time to remove the migrated parts:

* delete the old controller.
* delete the old templates (delete `admin-dev/themes/default/template/controller/{name}` folder.
* delete the related "legacy tests".

> NEVER call the legacy controller inside the new controller, it's a no go, no matter the reason!
