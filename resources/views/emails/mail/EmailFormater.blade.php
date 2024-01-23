<!DOCTYPE html>
<html>
    <head>
    <title>Message</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">
    <!-- Styles -->
    <style>
    </style>
 </head>
    <body>
        <!--Header-->
        <div style="width:90%; padding: 15px;">
            <img src="https://creditek.com.co/wp-content/uploads/thegem-logos/logo_2978351777cb32c351067e4dc4fff232_1x.png" alt="logo">
        </div>
        <div style="width: 100%">
            @if(is_string($email->message)) 
                <p style="text-align: justify"> {{ $email->message }} </p>
            @else
                <p style="text-align: justify"> {{ $email->message['text'] }} 
                    <a href={{$email->message['link_payment']}}> {{$email->message['link_payment']}}</a> y evita ser bloqueado
                </p>
            @endif
        </div>
        <div style="margin-top:30px; padding: 15px; width: 100%; background-color: #1391d0; display: block; text-align: center">
            <div style="padding: 1px">
                <p style="width: 100%; color: white;">2021 © Copyrights CrediTek</p>
                <p style="color: white; margin-top: -5px;">
                    <a style="color: white;text-decoration: none;" href="http://creditek.com.co/terminos-y-condiciones/">Términos y Condiciones</a> |<a style="color: white;text-decoration: none;" href="http://creditek.com.co/politivas-de-privacidad/"> Políticas de Privacidad</a>
                </p>
            </div>
        </div>
    </body>
</html>