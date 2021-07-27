<div class="row">
  <div class="col-sm-8">
    <ul class="list-inline"">
      <li>
        <i class="fa fa-fw fa-person"></i>
        USERNAME<br>
        <b><a href="/clientarea.php?action=productdetails&id={$serviceid}&dosinglesignon=1" target="_blank">{$username}</a></b>
      </li>
    </ul>
  </div>
  <div class="col-sm-4">
    <a href="/clientarea.php?action=productdetails&id={$serviceid}&dosinglesignon=1" target="_blank" class="btn btn-block btn-info">Launch Control Panel</a>
  </div>
</div>
<hr>
<div class="row">
  <div class="col-md-12">
    <ul class="list-inline">
      <li>
        <i class="fa fa-briefcase fa-fw"></i>
        PROJECTS<br>
        <b>{$projects}</b>
      </li>
      <li>
        <i class="fa fa-bolt fa-fw"></i>
        SERVICES<br>
        <b>{$services}</b>
      </li>
      <li>
        <i class="fa fa-money-bill-alt fa-fw"></i>
        ESTIMATED MONTHLY<br>
        <b>{$bill_estimate}</b>
      </li>
      <li>
        <i class="fa fa-money-bill-alt fa-fw"></i>
        CURRENT USAGE<br>
        <b>{$balance}</b>
      </li>
    </ul>
  </div>
</div>
