@props(['source'])

{{ match ($source) {
    'from_stock' => 'Από απόθεμα',
    'customer_supplied' => 'Έφερε ο πελάτης',
    'purchased_for_job' => 'Αγοράστηκε για τη δουλειά',
    default => $source,
} }}
