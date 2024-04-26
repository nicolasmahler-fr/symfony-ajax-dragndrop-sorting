# Symfony : Sorting list items using drag'n'drop and Ajax

## Requirements

- PHP > 8.2.\*
- Symfony (i.e : 7.0.6)
- Database (i.e : mariaDb)
- Composer
- Doctrine fixtures bundle (_optional_)
- Doctrine stof/doctrine-extensions-bundle
- Webpack Encore

## Steps

### 01. Setup the project

#### Install new Symfony webApp :

`symfony new my_project_directory --version="7.0.*" --webapp` .

#### Create Database

**.env file**
`DATABASE_URL="mysql://root:@127.0.0.1:3306/howto_sads?serverVersion=5.7"`

`symfony console d:d:c`

#### Create items entity :

`symfony console make:entity`

_Fields :_

- name / string / 255 / not null
- position / int / not null

#### Create table :

`symfony console make:migration`
`symfony console doctrine:migrations:migrate`

#### Create fixtures

`composer require --dev orm-fixtures`

**src/DataFixtures/AppFixtures.php :**
Create a loop of 10 items, with unique name (ie : item-1, item-2 ... item-10) and set each a unique position (0 -> 9).
**IMPORTANT:** Ensure the positions are set from 0!

#### Load Fixtures

`symfony console d:f:l`

#### Make Controller

Create a new controller to list all items
`symfony console make:controller`

    `SortableItemsController`

**Files :**
src/Controller/SortableItemsController.php
src/Repository/SortableItemsRepository.php
templates/sortableItems/index.html.twig

#### List items per position

**src/Controller/SortableItemsController.php**

```
public function index(SortableItemsRepository $repo): Response
{
$items = $repo->findBy([], ['position' => 'asc']);

    return $this->render('sortableItems/index.html.twig', [
        'items' => $items
    ]);
 }
```

**templates/sortableItems/index.html.twig**

```
<ul>
    {% for item in items %}
        <li>
            {{item.name}} <span>{{item.position}}</span>
        </li>
    {% endfor %}
</ul>
```

### 02. Make it sortable

#### Frontend stuff

#### Install Webpack Encore

We need to install js libraries (Jquery/UI, axios) and write some js code to enable drag'n'drop and update data with Ajax, so we need to enable Webpack :

`composer require symfony/webpack-encore-bundle`

#### Install webpack dependencies :

`yarn install`

**NOTICE :** _For this simple purpose we don't need bootstrap, so ensure to delete it's import from : 'assets/app.js' in order to avoid compilation error_

#### Build the assets

`yarn dev`

#### Install Jquery

I know that this can be disappointing as Jquery is mostly considered as totally outdated nowadays, but we need it in order to use JqueryUI, which is currently still the easiest way to add drag'n'drop in a web app.

`yarn add jquery`

#### Enable Jquery globally in our App

**assets/app.js**

```
const $ = require("jquery");
```

#### Install JqueryUI

This user interface library will enable drag'n'drop in our page. We chose the 1.13 specific version because it comes directly with drop, select and sort widgets.

`composer require components/jqueryui`
`yarn add jquery-ui@1.13.0`

#### Enable JqueryUI widgets

**assets/app.js**

```
require("jquery-ui/ui/widgets/droppable");
require("jquery-ui/ui/widgets/sortable");
require("jquery-ui/ui/widgets/selectable");
```

#### Enable drag'n'drop in the page

We first enable jqueryUI widgets on the DOM element with "js-sort" id.
**assets/app.js**

```
$("#js-sort").sortable({
  stop: function (event, ui) {
    console.log("drag'n'drop me!");
  },
});
```

Then we need to add this id to our "ul" and also add a data attribute to each "li" (usefull a bit later when we'll need to save items position).

**templates/sortableItems/index.html.twig**

```
<ul id="js-sort">
    {% for item in items %}
        <li data-id="{{item.id}}">
            {{item.name}} <span>{{item.position}}</span>
        </li>
    {% endfor %}
</ul>
```

_At this point, we can already drag'n'drop our items in the page, though this is not persistant when refreshing the page (don't forget to run "yarn dev" in the terminal to compile the assets if you want to test)..._

### ... And backend stuff

#### Install STOF doctrine extension bundle

This doctrine bundle extension add the ability to easily make specific fields sortable (and even more, just check its documentation). It particulary solves the fact that when we update a position, we need that this field needs to be a unique integer (two items shouldn't have the same position number). The sortable method ensure that, by automatically reassign correct and unique value to each item.

`composer require stof/doctrine-extensions-bundle`

#### Enable sortable method in STOF configuration file

**config/packages/stof_doctrine_extensions.yaml**

```
stof_doctrine_extensions:
  default_locale: en_US
  orm:
    default:
      sortable: true
```

#### Set item's position field as sortable

**src/Entity/Item.php**

```
use Gedmo\Mapping\Annotation\SortablePosition;
use Doctrine\DBAL\Types\Types;
...
#[SortablePosition]
#[ORM\Column(type: Types::INTEGER)]
private ?int $position = null;
```

#### Create a function to update item position

This method will be called in Ajax each time we release a field using drag'n'drop.

**src/Controller/SortableItemsController.php**

```
    /**
     * Reorder using Ajax (drag'n'drop)
     */
    #[Route('/reorder-items', name: 'update_item_pos')]
    public function updateItemPosition(Request $request, EntityManagerInterface $manager)
    {
        $item_id = $request->get('id');
        $position = $request->get('position');

        $item = $manager->getRepository(Item::class)->find($item_id);
        if (!$item) {
            return new JsonResponse(['error' => 'Item not found'], 404);
        }

        $item->setPosition($position);

        try {
            $manager->persist($item);
            $manager->flush();
            return new JsonResponse(true);
        } catch (\PDOException $e) {
            // Log the error to the console or error log
            error_log('PDOException occurred: ' . $e->getMessage());

            // Return a JSON response with an error message
            return new JsonResponse(['error' => 'An error occurred while updating item position'], 500);
        }
    }

```

#### Update items using ajax

We're now almost done. All we need to do now is to call the previous method in Ajax, so let's update our js script.

**assets/app.js**

```
$("#js-sort").sortable({
  stop: function (event, ui) {
    let base_url = window.location.origin;
    let element_id = ui.item[0].dataset.id; //Get li data-id (= product id)
    let position = ui.item.index();
    let link =
      base_url + "/reorder-items?id=" + element_id + "&position=" + position; //See route in item controller
    $.ajax({
      type: "POST",
      url: link,
      data: {
        position: position,
      },
      success: function (result) {
        console.log(result);
      },
      error: function (error) {
        console.log(error);
      },
    });
  },
});
```
