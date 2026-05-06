<x-mail::message>
# Hello!

**{{ $inviter->name }}** has invited you to join the **{{ $tenant->name }}** workspace on {{ config('app.name') }}.

@if(!empty($roleNames))
You have been assigned the following role(s):
@foreach($roleNames as $roleName)
- **{{ $roleName }}**
@endforeach
@endif

<x-mail::button :url="config('app.frontend_url') . '/join?token=' . $invitation->token">
Accept Invitation
</x-mail::button>

If you don't have an account yet, you will be able to create one after clicking the button above.

If you didn't expect this invitation, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
