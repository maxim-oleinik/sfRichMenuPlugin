<?php
/**
 * sfRichMenuComponents
 *
 * @author  Rustam Miniahmetov <pycmam@gmail.com>
 */

class sfRichMenuComponents extends sfComponents
{
    /**
     * Генератор меню
     */
    public function executeMenu(sfWebRequest $request)
    {
        $items = sfConfig::get('app_sf_rich_menu_'.$this->menu, array());

        $entry = $this->context->getController()->getActionStack()->getLastEntry();

        $currentAction = $entry->getActionName();
        $currentModule = $entry->getModuleName();

        $default = array('attributes' => array(), 'link_attributes' => array());

        $prevIdx  = false;
        $itemKeys = array_keys($items);
        foreach ($items as $idx => &$item) {

            // проверка привилегий
            if (isset($item['credentials'])) {
                $result = false;
                foreach ($item['credentials'] as $credential) {
                    $result = $result || $this->getUser()->hasCredential($credential);
                }

                // не показывать элемент меню, если недостаточно привилегий
                if (! $result) {
                    $item = false;
                    continue;
                }
            }

            $item = array_merge($default, $item);

            // Отметить первый и последний элементы
            if (false === $prevIdx) {
                $this->_addClassAttribute($item['attributes'], 'first');
            }
            if (end($itemKeys) == $idx) {
                $this->_addClassAttribute($item['attributes'], 'last');
            }

            $patterns = preg_split('/\s*;\s*/', $item['active_pattern']);
            $isActive = false;

            foreach($patterns as $pattern) {
                if (preg_match('/^(\w+)\[(\*|[@=\-\w]+)\]$/', trim($pattern), $matches)) {
                    $module = $matches[1];
                    $isActive = $isActive || $currentModule == $module;

                    if ($matches[2] != '*' && $isActive) {
                        $actions = preg_split('/\s*,\s*/', $matches[2]);
                        $isActive = false;

                        foreach($actions as $action) {
                            if (strpos($action, '@')) {
                                list($action, $param) = explode('@', $action, 2);
                                @list($param, $value) = explode('=', $param, 2);

                                $isActive = $isActive ||
                                    ($request->getParameter(trim($param)) == trim($value) && $currentAction == $action);
                            } else {
                                $isActive = $isActive || $currentAction == $action;
                            }
                        }
                    }

                    if ($isActive) {
                        // пометить текущий элеемент меню классом активности
                        $activeClass = isset($this->activeClass) ? $this->activeClass : 'active';
                        $this->_addClassAttribute($item['attributes'], $activeClass);

                        // пометить предыдущий элемент меню классом 'before-active'
                        if (false !== $prevIdx) {
                            $this->_addClassAttribute($items[$prevIdx]['attributes'], 'before-active');
                        }

                        // установить слоты
                        if (isset($item['slots'])) {
                            foreach ($item['slots'] as $slotName => $slotValue) {
                                $this->getResponse()->setSlot($slotName, $slotValue);
                            }
                        }

                        break;
                    }
                }
            }

            $prevIdx = $idx;
        }

        $this->setVar('items', $items, $noEsc = true);
    }


    /**
     * Добавить в указанный массив новый класс в соответствующее поле
     *
     * @param  array  $target - Массив с ключом "class"
     * @param  string $class  - Строка, которую надо добавить
     * @return void
     */
    private function _addClassAttribute(array &$target, $class)
    {
        $classes = array();
        if (isset($target['class'])) {
            $classes[] = $target['class'];
        }
        $classes[] = $class;
        $target['class'] = implode(' ', $classes);
    }

}
