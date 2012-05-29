<?php

namespace BoxUK\Bundle\IrcLogsAppBundle\Twig;

/**
 *
 */
class InlineExtension extends \Twig_Extension
{

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            'inlinify' => new \Twig_Filter_Method($this, 'inlinify', array('is_safe' => array('html'))),
            'linkify' => new \Twig_Filter_Method($this, 'linkify', array('is_safe' => array('html'))),
        );
    }

    /**
     * @param string $message
     * @return string
     */
    public function inlinify($message)
    {
        return $this->imagify(
            $this->linkify( $message )
        );
    }

    /**
     * Converts text that look like urls to links
     *
     * @param string $message
     * @return string
     */
    public function linkify($message)
    {
        $message = preg_replace(self::getSpotifyUrlFormatRegex(), '<a href="$0" target="_blank">$0</a>', $message);

        return preg_replace(self::getUrlFormatRegex(), '<a href="$0" target="_blank">$0</a>', $message);
    }

    /**
     * Gets a regex that matches a url
     * Modified version of @see http://blogs.lse.ac.uk/clt/2008/04/23/a-regular-expression-to-match-any-url/
     *
     * @return string The regular expression
     */
    private static function getUrlFormatRegex()
    {
        return <<<EOF
        |
        ([A-Za-z]{3,9})://
        ([-;:&=\+\$,\w]+@{1})?
        ([-A-Za-z0-9\.]+)+
        :?(\d+)?
        (
        (/[-\+~%/\.\w]+)?
        \??([-\+=&;%@,\.\w]+)?
        \#?([a-zA-Z0-9-\._~:@/?]+)?
        )?
        |x
EOF;
    }

    /**
     * Regex to match a spotify link
     *
     * @return string The regular expression
     */
    private static function getSpotifyUrlFormatRegex()
    {
        return <<<EOF
        /(spotify:(?:
            (?:artist|album|track|user:[^:]+:playlist):[a-zA-Z0-9]+
            |user:[^:]+
            |search:(?:[-\w$\.+!*'(),]+|%[a-fA-F0-9]{2})+
            ))
          /x
EOF;
    }

    public function imagify($message)
    {
        preg_match_all( '/<a href="(.*(jpg|gif|png))"/', $message, $matches );

        if ( !empty($matches[0]) ) {
            foreach ( $matches[1] as $url ) {
                $message .= sprintf(
                    '<span class="image">' .
                        '<a href="%1$s"><img src="%1$s" /></a>' .
                    '</span>',
                    $url
                );
            }
        }

        return $message;
    }


    public function getName()
    {
        return 'inline';
    }
}
