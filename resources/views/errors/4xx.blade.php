@include('errors.layout', ['code' => $exception->getStatusCode(), 'title' => 'Request not completed', 'text' => 'That request could not be completed.'])
