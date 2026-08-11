<p>
<strong>Forfatter:</strong><span>{{$customerName}}</span>
<br>
<strong>E-mail:</strong><span>{{$conv['customer_email']}}</span>
<p>
<strong>Helpdesk rapport:</strong>
<br>
    {!! $thread['body'] !!}
</p>
<hr>
<p>
    <a href="{{$freescoutUrl}}" class="btn btn-primary">Åben i Freescout</a>
</p>
