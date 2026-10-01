<?php

namespace App\Filament\Support;

use App\Http\Controllers\AdminCourseController;
use App\Http\Requests\CourseRequest;
use App\Models\Course;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Shares the validated, audited administration workflows with Filament actions. */
class AdminWorkflows
{
    public static function authorize(): void
    {
        $user = Auth::user();
        abort_unless($user && $user->role === 'admin' && ! $user->suspended_at && $user->hasVerifiedEmail() && $user->two_factor_secret && $user->two_factor_confirmed_at, 403);
        abort_unless(time() - (int) session('auth.password_confirmed_at', 0) < (int) config('auth.password_timeout', 10800), 403, 'Confirm your password again before changing administration records.');
    }

    /** @param array<string,mixed> $data */
    public static function request(array $data): Request
    {
        self::authorize();
        $request = Request::create('/admin', 'POST', $data);
        $request->setUserResolver(fn () => Auth::user());
        $request->setLaravelSession(app('session.store'));

        return $request;
    }

    /**
     * @param  class-string<\App\Http\Controllers\Controller>  $controller
     * @param  array<string,mixed>  $data
     */
    public static function run(string $controller, string $method, array $data, Model ...$records): Response
    {
        $request = self::request($data);
        try {
            /** @var callable $callback */
            $callback = [app($controller), $method];
            $response = self::withHttpRedirector(fn () => app()->call($callback, ['request' => $request, ...self::parameters($records)]));
        } catch (ValidationException $exception) {
            Notification::make()->title('Check the course or lesson')->body(implode(' ', \Illuminate\Support\Arr::flatten($exception->errors())))->danger()->persistent()->send();
            throw $exception;
        } catch (HttpExceptionInterface $exception) {
            if (in_array($exception->getStatusCode(), [409, 422, 503], true)) {
                $message = $exception->getMessage() ?: 'The provider is unavailable. Check configuration and try again.';
                Notification::make()->title('Action could not be completed')->body($message)->danger()->persistent()->send();
                throw ValidationException::withMessages(['reason' => $message]);
            }
            throw $exception;
        }
        Notification::make()->title('Saved successfully')->success()->send();

        return $response;
    }

    /**
     * @param  array<Model>  $records
     * @return array<string,Model>
     */
    private static function parameters(array $records): array
    {
        $parameters = [];
        foreach ($records as $record) {
            $parameters[lcfirst(class_basename($record))] = $record;
        }

        return $parameters;
    }

    /** @param array<string,mixed> $data */
    public static function saveCourse(array $data, ?Course $course = null): Course
    {
        $request = CourseRequest::createFrom(self::request($data));
        $route = new Route('POST', '/admin/courses/{course?}', []);
        $route->bind(Request::create($course ? '/admin/courses/'.$course->id : '/admin/courses'));
        if ($course) {
            $route->setParameter('course', $course);
        }
        $request->setRouteResolver(fn () => $route);
        $request->setContainer(app())->setRedirector(app('redirect'));
        $request->validateResolved();
        $controller = app(AdminCourseController::class);
        self::withHttpRedirector(fn () => $course ? $controller->update($request, $course) : $controller->store($request));

        return Course::where('slug', $data['slug'])->firstOrFail();
    }

    /** Livewire temporarily replaces Laravel's redirector; controllers return HTTP responses. */
    public static function withHttpRedirector(callable $callback): mixed
    {
        $previous = app('redirect');
        $redirector = new \Illuminate\Routing\Redirector(app('url'));
        $redirector->setSession(app('session.store'));
        app()->instance('redirect', $redirector);
        try {
            return $callback();
        } finally {
            app()->instance('redirect', $previous);
        }
    }
}
