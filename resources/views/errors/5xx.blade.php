@include('errors.layout', ['code' => $exception->getStatusCode(), 'title' => 'Something went wrong', 'text' => 'An unexpected error happened on our side. Please try again.'])
