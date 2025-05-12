<?php

namespace splitbrain\meh\ApiControllers;

use Jdenticon\Identicon;
use splitbrain\meh\ApiController;
use splitbrain\RingIcon\RingIconSVG;

/**
 * This is not a real API controller as it does not return JSON data but will directly output an image
 */
class AvatarApiController extends ApiController
{
    // local avatar types
    protected $local = [
        'ring',
        'multiavatar',
        'identicon',
        'mp',
        'blank',
    ];


    /**
     * Dispatch an avatar
     *
     * @param array $data
     * @return void
     */
    public function avatar($data): void
    {
        $seed = $data['seed'] ?? '';
        $isFallback = $data['fallback'] ?? false;

        $type = $this->app->conf('avatar');
        if ($isFallback) {
            // we returned here from gravatar
            $type = $this->app->conf('gravatar_fallback');
            if (!in_array($type, $this->local)) $type = '';
        }

        switch ($type) {
            case 'gravatar':
                $this->gravatar($seed);
                break;

            case 'ring':
                header('Content-Type: image/svg+xml');
                $icon = new RingIconSVG(256, 3);
                $icon->createImage($seed);
                break;

            case 'multiavatar':
                header('Content-Type: image/svg+xml');
                $icon = new \Multiavatar();
                echo $icon($seed, null, null);
                break;

            case 'identicon':
                header('Content-Type: image/svg+xml');
                $icon = new Identicon();
                $icon->setSize(256);
                $icon->setHash($seed);
                $icon->displayImage('svg');
                break;

            case 'blank':
                header('Content-Type: image/svg+xml');
                echo '<svg width="256" height="256" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"></svg>';
                break;

            case 'mp': // "Mystery Person"
            default:

                header('Content-Type: image/svg+xml');
                echo '<svg width="256" height="256" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M8 2a2.84 2.84 0 0 0-1.12.221c-.345.141-.651.348-.906.615v.003l-.001.002c-.248.269-.44.592-.574.96-.137.367-.203.769-.203 1.2 0 .435.065.841.203 1.209.135.361.327.68.574.95l.001.002c.254.267.558.477.901.624v.003c.346.141.723.21 1.12.21.395 0 .77-.069 1.117-.21v-.002c.343-.147.644-.357.892-.625.255-.268.45-.59.586-.952.138-.368.204-.774.204-1.21h.01c0-.43-.065-.831-.203-1.198a2.771 2.771 0 0 0-.585-.963 2.5 2.5 0 0 0-.897-.618A2.835 2.835 0 0 0 7.999 2zM8.024 10.002c-2.317 0-3.561.213-4.486.91-.462.35-.767.825-.939 1.378-.172.553-.226.975-.228 1.71L8 14.002h5.629c-.001-.736-.052-1.159-.225-1.712-.172-.553-.477-1.027-.94-1.376-.923-.697-2.124-.912-4.44-.912z" style="opacity:0.5"></path></svg>';
        }
        exit;
    }

    /**
     * Redirect to gravatar
     *
     * @param string $ident
     * @return void
     */
    protected function gravatar(string $ident): void
    {
        $fallback = (string) $this->app->conf('gravatar_fallback');
        if (in_array($fallback, $this->local)) {
            // Let our error handler take care of this
            $fallback = 404;
        }

        $gravatar = 'https://www.gravatar.com/avatar/' . $ident . '?s=256';
        $gravatar .= '&d=' . urlencode($fallback);
        $gravatar .= '&r=' . $this->app->conf('gravatar_rating');

        header('Location: ' . $gravatar);
        exit;
    }


}
