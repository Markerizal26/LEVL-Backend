<?php

namespace Tests\Unit\Foundation;

use App\Exceptions\ForbiddenException;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\ValidationException;
use Illuminate\Http\Request;
use Tests\TestCase;

class ExceptionHandlerTest extends TestCase
{
    public function test_resource_not_found_exception_returns_proper_response(): void
    {
        $exception = new ResourceNotFoundException('User not found');
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);
        $this->assertEquals(404, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertFalse($data['success']);
    }

    public function test_unauthorized_exception_returns_proper_response(): void
    {
        $exception = new UnauthorizedException('Not authenticated');
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);
        $this->assertEquals(401, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertFalse($data['success']);
    }

    public function test_forbidden_exception_returns_proper_response(): void
    {
        $exception = new ForbiddenException('Access denied');
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);
        $this->assertEquals(403, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertFalse($data['success']);
    }

    public function test_validation_exception_returns_proper_response(): void
    {
        $exception = new ValidationException('Validation failed');
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);
        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertFalse($data['success']);
    }

    public function test_error_response_structure_is_consistent(): void
    {
        $exceptions = [
            new ResourceNotFoundException('Not found'),
            new UnauthorizedException('Unauthorized'),
            new ForbiddenException('Forbidden'),
            new ValidationException('Validation error'),
        ];
        foreach ($exceptions as $exception) {
            $request = Request::create('/api/test', 'GET');
            $request->headers->set('Accept', 'application/json');
            $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
            $response = $handler->render($request, $exception);
            $data = json_decode($response->getContent(), true);
            $this->assertIsArray($data);
            $this->assertArrayHasKey('success', $data);
            $this->assertArrayHasKey('message', $data);
            $this->assertIsBool($data['success']);
            $this->assertIsString($data['message']);
            $this->assertFalse($data['success']);
        }
    }

    public function test_api_path_requests_receive_json_responses(): void
    {
        $exception = new ResourceNotFoundException('Test not found');
        $request = Request::create('/api/users/123', 'GET');
        $request->headers->set('Accept', 'application/json');
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function test_json_expecting_requests_receive_json_responses(): void
    {
        $exception = new ResourceNotFoundException('Test not found');
        $request = Request::create('/some/path', 'GET');
        $request->headers->set('Accept', 'application/json');
        $handler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $response = $handler->render($request, $exception);
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }
}
