<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class SearchType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('query')
            ->add('channel', null, array(
                    'required' => false,
                    'attr' => array(
                        'data-provide' => 'typeahead',
                        'data-source' => json_encode(array_values($options['channels'])),
                    )
                )
            )
            ->add('from', 'date', array(
                    'widget' => 'single_text',
                    'required' => false,
                    'format' => 'yyyy-MM-dd',
                    'data_timezone' => 'Zulu',
                    'user_timezone' => 'Zulu',
                    'attr' => array('data-datepicker' => '')
                )
            )
            ->add('to', 'date', array(
                    'widget' => 'single_text',
                    'required' => false,
                    'format' => 'yyyy-MM-dd',
                    'data_timezone' => 'Zulu',
                    'user_timezone' => 'Zulu',
                    'attr' => array('data-datepicker' => '')
                )
            )
            ->add('nick', null, array(
                'required' => false,
                'attr'     => array(
                    'data-provide' => 'typeahead',
                    'data-source'  => json_encode(array_values($options['nicks'])),
                )
            )
        )
        ;
    }

    public function getName()
    {
        return 'irc_search';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'csrf_protection'   => false,
            'show_legend'       => false,
            'data_class'        => 'BoxUK\Bundle\IrcLogsAppBundle\Form\Entity\Search',
            'channels'          => array(),
            'nicks'             => array(),
        );
    }
}
