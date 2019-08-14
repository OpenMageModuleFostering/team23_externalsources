<?php

/**
 * Team23 External Sources
 *
 * @category  Team23
 * @package   Team23_ExternalSources
 * @version   1.0.0
 * @copyright 2014 Team23 GmbH & Co. KG (http://www.team23.de)
 * @license   http://opensource.org/licenses/MIT The MIT License (MIT)
 */


class Team23_ExternalSources_Block_Html_Head extends Mage_Page_Block_Html_Head
{

    /**
     * Add external javascript source
     *
     * @param string $name
     * @param string $params
     * @return Team23_ExternalSources_Block_Html_Head
     */
    public function addExternalJs($name, $params = "")
    {
        $this->addExternalItem('external_js', $name, $params);

        return $this;
    }

    /**
     * Add external css source
     *
     * @param string $name
     * @param string $params
     * @return $this
     */
    public function addExternalCss($name, $params = "")
    {
        $this->addExternalItem('external_css', $name, $params);

        return $this;
    }

    /**
     * Add HEAD external item
     *
     * Allowed types:
     *  - js
     *  - js_css
     *  - skin_js
     *  - skin_css
     *  - rss
     *  - external_css
     *  - external_js
     *
     * @param string $type
     * @param string $name
     * @param string $params
     * @param string $if
     * @param string $cond
     * @return Mage_Page_Block_Html_Head
     */
    public function addExternalItem($type, $name, $params = null, $if = null, $cond = null)
    {
        $this->addItem($type, $name, $params, $if, $cond);

        return $this;
    }

    /**
     * Remove external item from HEAD entity
     *
     * @param string $type
     * @param string $name
     * @return Mage_Page_Block_Html_Head
     */
    public function removeExternalItem($type, $name)
    {
        $this->removeItem($type, $name);

        return $this;
    }

    /**
     * Get HEAD HTML with CSS/JS/RSS definitions
     *
     * @return string
     */
    public function getCssJsHtml()
    {
        // separate items by types
        $lines = array();

        foreach ($this->_data['items'] as $item)
        {
            if (!is_null($item['cond']) && !$this->getData($item['cond']) || !isset($item['name']))
                continue;

            $if     = !empty($item['if']) ? $item['if'] : '';
            $params = !empty($item['params']) ? $item['params'] : '';

            switch ($item['type'])
            {
                case 'external_css': // cdn
                case 'external_js':  // cdn
                case 'js':           // js/*.js
                case 'skin_js':      // skin/*/*.js
                case 'js_css':       // js/*.css
                case 'skin_css':     // skin/*/*.css
                    $lines[$if][$item['type']][$params][$item['name']] = $item['name'];
                    break;
                default:
                    $this->_separateOtherHtmlHeadElements($lines, $if, $item['type'], $params, $item['name'], $item);
                    break;
            }
        }

        // prepare HTML
        $shouldMergeJs  = Mage::getStoreConfigFlag('dev/js/merge_files');
        $shouldMergeCss = Mage::getStoreConfigFlag('dev/css/merge_css_files');
        $html           = '';

        foreach ($lines as $if => $items)
        {
            if (empty($items))
                continue;

            if (!empty($if))
                $html .= '<!--[if '.$if.']>'."\n";

            // prepare external css sources
            if (isset($items['external_css']))
            {
                foreach ($items['external_css'] as $external_param => $external_items)
                {
                    foreach ($external_items as $external)
                        $html .= sprintf('<link rel="stylesheet" type="text/css" href="%s" %s/>', $external, $external_param);
                }
            }

            // static and skin css
            $html .= $this->_prepareStaticAndSkinElements('<link rel="stylesheet" type="text/css" href="%s"%s />' . "\n",
                empty($items['js_css']) ? array() : $items['js_css'],
                empty($items['skin_css']) ? array() : $items['skin_css'],
                $shouldMergeCss ? array(Mage::getDesign(), 'getMergedCssUrl') : null
            );

            // prepare external js sources
            if (isset($items['external_js']))
            {
                foreach ($items['external_js'] as $external_param => $external_items)
                {
                    foreach ($external_items as $external)
                        $html .= sprintf('<script type="text/javascript" src="%s" %s></script>', $external, $external_param);
                }
            }

            // static and skin javascripts
            $html .= $this->_prepareStaticAndSkinElements('<script type="text/javascript" src="%s"%s></script>' . "\n",
                empty($items['js']) ? array() : $items['js'],
                empty($items['skin_js']) ? array() : $items['skin_js'],
                $shouldMergeJs ? array(Mage::getDesign(), 'getMergedJsUrl') : null
            );

            // other stuff
            if (!empty($items['other']))
                $html .= $this->_prepareOtherHtmlHeadElements($items['other']) . "\n";

            if (!empty($if))
                $html .= '<![endif]-->'."\n";
        }

        return $html;
    }

}