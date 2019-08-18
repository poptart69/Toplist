<?php

use Phalcon\Cache\Backend\File as BackFile;
use Phalcon\Cache\Frontend\Data as FrontData;
use Phalcon\Mvc\View;
use Phalcon\Paginator\Adapter\NativeArray;

class ToolsController extends BaseController {

    public function indexAction() {
        $this->updateItems();
    }

    public function itemsAction() {

    }

    public function searchAction() {
        $this->view->setRenderLevel(View::LEVEL_ACTION_VIEW);

        $data   = $this->getItemList();
        $search = $this->request->getPost("search", "string");
        $found  = [];

        if ($search != null && $search != '') {
            foreach ($data as $item) {
                $itemName = $item['name'];
                if (stripos(strtolower($itemName), strtolower($search)) !== false) {
                    $found[] = $item;
                }
            }
        } else {
            $found = $data;
        }

        $itemList = (new NativeArray([
            'data'  => $found,
            'limit' => 50,
            'page'  => $this->request->getPost("page", "int", 1)
        ]))->getPaginate();

        $this->view->icon_url = 'https://www.osrsbox.com/osrsbox-db/items-icons/';
        $this->view->itemList = $itemList;
    }

    private function getItemList() {
        $path     = $this->getConfig()->path("core.base_path");
        $cache    = new BackFile(new FrontData(), ['cacheDir' =>  $path.'/app/compiled/']);
        $itemList = $cache->get("items.data.cache", 86400);

        if (!$itemList) {
            $itemList = $this->updateItems();
            $cache->save("items.data.cache", $itemList);
        }

        return $itemList;
    }

    /**
     * Grabs new items from OSRSBOX if the cache is expired. Falls back on the saved json file if fails.
     * @return array|mixed
     */
    private function updateItems() {
        $path = $this->getConfig()->path("core.base_path");
        $data = $this->getFile();

        if (!$data) {
            return json_decode(file_get_contents($path.'/resources/item-data.json'), true);
        }

        $oldData = $data;
        $data    = [];

        foreach ($oldData as $key => $value) {
            $itemId   = $value['id'];
            $itemName = $value['name'];
            $data[]   = ['id' => $itemId, 'name' => $itemName];
        }

        file_put_contents($path.'/resources/item-data.json', json_encode($data));
        return $data;
    }

    private function getFile() {
        $url = 'https://www.osrsbox.com/osrsbox-db/items-summary.json';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $data = curl_exec($curl);
        curl_close($curl);
        return json_decode($data, true);
    }

}