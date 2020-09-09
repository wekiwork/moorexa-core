<?php

return array (
  'b2fba9268194e7dcb0d19f6368b72eeb' => 
  array (
    'query' => 'SELECT * FROM session_storage WHERE session_identifier = :session_identifier AND user_agent = :user_agent  ',
    'bind' => 
    array (
      'session_identifier' => '52b7a47a1a_state-manager',
      'user_agent' => '48a49bc72423076a1c2400df983fd43a',
    ),
  ),
  'f2fe729756a93a45dd67e8926c835a41' => 
  array (
    'query' => 'SELECT * FROM session_storage {where}',
    'bind' => 
    array (
    ),
  ),
  'bd9b0bbcc349d8fbef5e253d9a2cdc2f' => 
  array (
    'query' => 'UPDATE session_storage SET session_value = :session_value  WHERE session_identifier = :session_identifier AND user_agent = :user_agent  ',
    'bind' => 
    array (
      'session_value' => 'czo4MDoiOyJzOjY0OiJhQnUyc0h6dWlvNEVUZHR1V0Vpd1BLOHR3d0d5UkZ2bWZVRVlsN3NVaHB1L0M3S1NTQzhuU1hKZTRvOTNPd1dDIjsiOjI3OnMiOw==',
      'session_identifier' => '52b7a47a1a_state-manager',
      'user_agent' => '48a49bc72423076a1c2400df983fd43a',
    ),
  ),
  'f2b2d49ffd3e531bbddfe737ac057624' => 
  array (
    'query' => 'UPDATE ongoingRequests SET handshake_codes = :handshake_codes  WHERE cookie_hash = :cookie_hash ',
    'bind' => 
    array (
      'handshake_codes' => 'aaf8c5fd37,38f8d9463e',
      'cookie_hash' => '9c6fc1041f3325967b0927d51d9fd1f7',
    ),
  ),
);
