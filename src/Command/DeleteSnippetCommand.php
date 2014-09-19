<?php

namespace eecli\Command;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DeleteSnippetCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $name = 'delete:snippet';

    /**
     * {@inheritdoc}
     */
    protected $description = 'Delete a snippet.';

    /**
     * {@inheritdoc}
     */
    protected function getArguments()
    {
        return array(
            array(
                'name', // name
                InputArgument::IS_ARRAY | InputArgument::REQUIRED, // mode
                'The name of the snippet(s) to delete.', // description
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return array(
            array(
                'global', // name
                null, // shortcut
                InputOption::VALUE_NONE, // mode
                'Delete a global snippet.', // description
                null, // default value
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
    {
        $names = $this->argument('name');

        $siteId = $this->option('global') ? 0 : ee()->config->item('site_id');
        $siteName = $this->option('global') ? 'global_snippets' : ee()->config->item('site_short_name');

        $query = ee()->db->select('snippet_id, snippet_name, snippet_contents')
            ->where('site_id', $siteId)
            ->where_in('snippet_name', $names)
            ->get('snippets');

        $snippets = array();

        foreach ($query->result() as $row) {
            $snippets[$row->snippet_name] = $row;
        }

        $query->free_result();

        foreach ($names as $name) {
            if (! isset($snippets[$name])) {
                $this->error('Snippet '.$name.' not found.');

                continue;
            }

            $snippet = $snippets[$name];

            ee()->db->delete('snippets', array('snippet_id' => $snippet->snippet_id));

            ee()->extensions->call('eecli_delete_snippet', $snippet->snippet_id, $snippet->snippet_name, $snippet->snippet_contents, $siteId, $siteName);

            $this->info('Snippet '.$name.' deleted.');
        }
    }
}