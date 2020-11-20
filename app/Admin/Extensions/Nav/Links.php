<?php
namespace App\Admin\Extensions\Nav;

class Links
{
    public function __toString()
    {


        $horizonLink = admin_url('horizon');

        return <<<HTML
<li>
    <a href="{$horizonLink}" target="_blank">
      <i class="fa fa-coffee"></i>
      <span>Horizon</span>
    </a>
</li>


HTML;
    }
}
