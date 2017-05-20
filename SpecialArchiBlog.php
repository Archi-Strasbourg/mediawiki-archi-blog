<?php
/**
 * SpecialArchiBlog class.
 */

namespace ArchiBlog;

/**
 * SpecialPage Special:ArchiBlog that lists recent news articles.
 */
class SpecialArchiBlog extends \SpecialPage
{
    /**
     * SpecialArchiBlog constructor.
     */
    public function __construct()
    {
        parent::__construct('ArchiBlog');
    }

    /**
     * Send a request to the MediaWiki API.
     *
     * @param array $options Request parameters
     *
     * @return array
     */
    private function apiRequest(array $options)
    {
        $params = new \DerivativeRequest(
            $this->getRequest(),
            $options
        );
        $api = new \ApiMain($params);
        $api->execute();

        return $api->getResult()->getResultData();
    }

    /**
     * Display the special page.
     *
     * @param string $subPage
     *
     * @return void
     */
    public function execute($subPage)
    {
        $output = $this->getOutput();
        $this->setHeaders();

        $news = $this->apiRequest(
            [
                'action'      => 'ask',
                'query'       => '[[Actualité:+]]|?Date de publication|sort=Date de publication|order=desc|limit=10'
            ]
        );
        $changes = [];
        foreach ($news['query']['results'] as $change) {
            if (isset($change['fulltext'])) {
                $title = \Title::newFromText($change['fulltext']);
                $changes[$title->getArticleID()] = $change['fulltext'];
            }
        }
        $extracts = $this->apiRequest(
            [
                'action'          => 'query',
                'prop'            => 'extracts|images',
                'titles'          => implode('|', $changes),
                'explaintext'     => true,
                'exintro'         => true,
                'exchars'         => 250,
                'exsectionformat' => 'plain',
                'exlimit'         => 10,
                'imlimit'         => 500,
            ]
        );
        foreach ($changes as $id => $name) {
            if (isset($extracts['query']['pages'][$id]['extract'])) {
                $title = \Title::newFromText($name);
                $creationDate = new \DateTime($title->getEarliestRevTime());
                $wikitext = '=='.$title->getText().'=='.PHP_EOL;
                if (isset($extracts['query']['pages'][$title->getArticleID()]['images'])) {
                    $wikitext .= '[['.$extracts['query']['pages'][$title->getArticleID()]['images'][0]['title'].
                        '|thumb|left|100px]]';
                }
                $wikitext .= $extracts['query']['pages'][$id]['extract']['*'].PHP_EOL.PHP_EOL;
                $wikitext .= '[['.$title.'|'.wfMessage('readmore')->parse().']]'.PHP_EOL.PHP_EOL;
                $output->addWikiText($wikitext);
                $output->addHTML('<div style="clear:both;"></div>');
            }
        }
        $output->addWikiText('[[Special:Toutes les pages/Actualité:|'.wfMessage('allblog')->parse().']]');
    }

    /**
     * Return the special page category.
     *
     * @return string
     */
    public function getGroupName()
    {
        return 'pages';
    }
}
