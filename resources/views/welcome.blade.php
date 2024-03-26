@extends('user_layouts.master')

@section('content')
@include('user_layouts.hero-section')
@include('user_layouts.blog-section')

@endsection

@section('script')
<script src="https://www.gstatic.com/firebasejs/7.23.0/firebase.js"></script>
<script>

    var firebaseConfig = {
        apiKey: "AIzaSyCL0bO5o9yx3FetzByeblq4mV3bsXjXTws",
        authDomain: "delight-77560.firebaseapp.com",
        projectId: "delight-77560",
        storageBucket: "delight-77560.appspot.com",
        messagingSenderId: "461564753337",
        appId: "1:461564753337:web:913e9334732114e80db268"
    };

    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    function initFirebaseMessagingRegistration() {
            messaging
            .requestPermission()
            .then(function () {
                return messaging.getToken()
            })
            .then(function(token) {
                console.log(token);

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url: '{{ route("save-token") }}',
                    type: 'POST',
                    data: {
                        token: token
                    },
                    dataType: 'JSON',
                    success: function (response) {
                        alert('Token saved successfully.');
                    },
                    error: function (err) {
                        console.log('User Chat Token Error'+ err);
                    },
                });

            }).catch(function (err) {
                console.log('User Chat Token Error'+ err);
            });
     }

    messaging.onMessage(function(payload) {
        const noteTitle = payload.notification.title;
        const noteOptions = {
            body: payload.notification.body,
            icon: payload.notification.icon,
        };
        new Notification(noteTitle, noteOptions);
    });

</script>
@endsection
