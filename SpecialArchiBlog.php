<?php

namespace ArchiBlog;

class SpecialArchiBlog extends \SpecialPage
{
    public function __construct()
    {
        parent::__construct('ArchiBlog');
    }

    private function apiRequest($options)
    {
        $params = new \DerivativeRequest(
            $this->getRequest(),
            $options
        );
        $api = new \ApiMain($params);
        $api->execute();
        return $api->getResult()->getResultData();
    }

    public function execute($par)
    {
        $output = $this->getOutput();
        $this->setHeaders();

        $news = $this->apiRequest(
            array(
                'action'=>'query',
                'list'=>'recentchanges',
                'rcnamespace'=>NS_NEWS,
                'rclimit'=>10,
                'rctype'=>'new'
            )
        );
        $changes = array();
        foreach ($news['query']['recentchanges'] as $change) {
            if (isset($change['title'])) {
                $changes[$change['pageid']] = $change['title'];
            }
        }
        $extracts = $this->apiRequest(
            array(
                'action'=>'query',
                'prop'=>'extracts',
                'titles'=>implode('|', $changes),
                'explaintext'=>true,
                'exintro'=>true,
                'exchars'=>250,
                'exsectionformat'=>'plain',
                'exlimit'=>10
            )
        );
        $wikitext = '';
        foreach ($changes as $id => $name) {
            if (isset($extracts['query']['pages'][$id]['extract'])) {
                $title = \Title::newFromText($name);
                $wikitext .= '=='.$title->getText().'=='.PHP_EOL;
                $wikitext .= $extracts['query']['pages'][$id]['extract']['*'].PHP_EOL.PHP_EOL;
                $wikitext .= '[['.$title->getFullText().'|Lire la suite]]'.PHP_EOL.PHP_EOL;
            }
        }

        $output->addWikiText($wikitext);

    }

    public function getGroupName()
    {
           return 'pages';
    }
}
