<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class SupabaseService
{
    protected string $url;
    protected string $key;
    protected string $serviceRoleKey;

    public function __construct()
    {
        $this->url = config('supabase.url');
        $this->key = config('supabase.key');
        $this->serviceRoleKey = config('supabase.service_role_key');
    }

    /**
     * Query data from a Supabase table
     *
     * @param string $table Table name
     * @param array $filters Query filters (e.g., ['status' => 'eq.active'])
     * @param array $options Additional options (select, order, limit, etc.)
     * @return Response
     */
    public function from(string $table, array $filters = [], array $options = []): Response
    {
        $url = config('supabase.database.url') . '/' . $table;

        $query = array_merge($filters, $options);

        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->get($url, $query);
    }

    /**
     * Insert data into a Supabase table
     *
     * @param string $table Table name
     * @param array $data Data to insert
     * @return Response
     */
    public function insert(string $table, array $data): Response
    {
        $url = config('supabase.database.url') . '/' . $table;

        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->post($url, $data);
    }

    /**
     * Update data in a Supabase table
     *
     * @param string $table Table name
     * @param array $filters Query filters
     * @param array $data Data to update
     * @return Response
     */
    public function update(string $table, array $filters, array $data): Response
    {
        $url = config('supabase.database.url') . '/' . $table;

        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->patch($url . '?' . http_build_query($filters), $data);
    }

    /**
     * Delete data from a Supabase table
     *
     * @param string $table Table name
     * @param array $filters Query filters
     * @return Response
     */
    public function delete(string $table, array $filters): Response
    {
        $url = config('supabase.database.url') . '/' . $table;

        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $this->key,
            'Content-Type' => 'application/json',
        ])->delete($url . '?' . http_build_query($filters));
    }

    /**
     * Upload a file to Supabase Storage
     *
     * @param string $bucket Bucket name
     * @param string $path File path in bucket
     * @param mixed $file File content or path
     * @param array $options Upload options
     * @return Response
     */
    public function uploadFile(string $bucket, string $path, $file, array $options = []): Response
    {
        $url = config('supabase.storage.url') . '/object/' . $bucket . '/' . $path;

        $headers = [
            'apikey' => $this->serviceRoleKey,
            'Authorization' => 'Bearer ' . $this->serviceRoleKey,
        ];

        if (isset($options['contentType'])) {
            $headers['Content-Type'] = $options['contentType'];
        }

        return Http::withHeaders($headers)->attach('file', $file)->post($url);
    }

    /**
     * Get a public URL for a file in Supabase Storage
     *
     * @param string $bucket Bucket name
     * @param string $path File path in bucket
     * @return string
     */
    public function getPublicUrl(string $bucket, string $path): string
    {
        return config('supabase.storage.url') . '/object/public/' . $bucket . '/' . $path;
    }

    /**
     * List files in a Supabase Storage bucket
     *
     * @param string $bucket Bucket name
     * @param string $path Path prefix
     * @return Response
     */
    public function listFiles(string $bucket, string $path = ''): Response
    {
        $url = config('supabase.storage.url') . '/object/list/' . $bucket;

        return Http::withHeaders([
            'apikey' => $this->serviceRoleKey,
            'Authorization' => 'Bearer ' . $this->serviceRoleKey,
            'Content-Type' => 'application/json',
        ])->post($url, ['prefix' => $path]);
    }

    /**
     * Delete a file from Supabase Storage
     *
     * @param string $bucket Bucket name
     * @param string $path File path in bucket
     * @return Response
     */
    public function deleteFile(string $bucket, string $path): Response
    {
        $url = config('supabase.storage.url') . '/object/' . $bucket . '/' . $path;

        return Http::withHeaders([
            'apikey' => $this->serviceRoleKey,
            'Authorization' => 'Bearer ' . $this->serviceRoleKey,
        ])->delete($url);
    }

    /**
     * Sign in with email and password
     *
     * @param string $email User email
     * @param string $password User password
     * @return Response
     */
    public function signIn(string $email, string $password): Response
    {
        $url = config('supabase.auth.url') . '/token?grant_type=password';

        return Http::withHeaders([
            'apikey' => $this->key,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'email' => $email,
            'password' => $password,
        ]);
    }

    /**
     * Sign up a new user
     *
     * @param string $email User email
     * @param string $password User password
     * @param array $metadata Additional user metadata
     * @return Response
     */
    public function signUp(string $email, string $password, array $metadata = []): Response
    {
        $url = config('supabase.auth.url') . '/signup';

        return Http::withHeaders([
            'apikey' => $this->key,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'email' => $email,
            'password' => $password,
            'data' => $metadata,
        ]);
    }

    /**
     * Get user by access token
     *
     * @param string $token Access token
     * @return Response
     */
    public function getUser(string $token): Response
    {
        $url = config('supabase.auth.url') . '/user';

        return Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer ' . $token,
        ])->get($url);
    }
}
