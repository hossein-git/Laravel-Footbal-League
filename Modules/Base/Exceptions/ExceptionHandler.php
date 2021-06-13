<?php


namespace Modules\Base\Exceptions;


use Doctrine\Instantiator\Exception\UnexpectedValueException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;
use Modules\Base\Traits\ApiResponse;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;

trait ExceptionHandler
{
    use ApiResponse;

    function getMessage($classname)
    {
        $classname = get_class($classname);
        if ($pos = strrpos($classname, '\\')) {
            $classname = substr($classname, $pos + 1);
        }
        return $classname;
    }

    private function handleApiException($request, \Throwable $e)
    {
        return $this->handleException($request, $e);
    }


    //status default is Failed

    public function handleException($request, $e, $httpCode = null, $status = 'Failed')
    {
        if ($e instanceof NotFoundHttpException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(404, $httpCode), $e->getCode(), $status);
        } elseif ($e instanceof NotFoundResourceException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(404, $httpCode), $e->getCode(), $status);
        } elseif ($e instanceof ModelNotFoundException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(404, $httpCode), $e->getCode(), $status,$e->getModel() .' ID: '. implode(',',$e->getIds()));
        } elseif ($e instanceof EntryNotFoundException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(404, $httpCode), $e->getCode(), $status);
        } elseif ($e instanceof AccessDeniedException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(403, $httpCode), $e->getCode(), $status);
        } elseif ($e instanceof InternalErrorException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(500, $httpCode), $e->getCode(), $status);
        }  elseif ($e instanceof AuthorizationException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(401, $httpCode), $e->getCode(), $status);
        } elseif ($e instanceof UnauthorizedException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(403, $httpCode), $e->getCode(), $status);
        } elseif ($e instanceof QueryException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(409, $httpCode), 409, $status);
        } elseif ($e instanceof UnexpectedValueException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(400, $httpCode), $e->getCode(), $status);
        } elseif ($e instanceof ValidationException) {
            $e = $this->convertValidationExceptionToResponse($e, $request); return $this->setMetaData([], $e->getOriginalContent())->badRequestResponse(100);
        } elseif ($e instanceof AuthenticationException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(400, $httpCode), $e->getCode(), $status);
        }  elseif ($e instanceof \Lauthz\Exceptions\UnauthorizedException) {
            return $this->handle($e, $e->getMessage(), $this->getHttpCode(400, $httpCode), $e->getCode(), $status);
        } else {
//            return $e;
            return $this->customResponse($e, $status, 500, 0);
            return $this->failedResponse();
        }
    }

    /**
     * @param \Exception $e
     * @param        $message
     * @param        $httpCode
     * @param        $statusCode
     * @param string $status
     * @param $additional
     * @return \Illuminate\Http\JsonResponse
     */
    private function handle(\Exception $e, $message, $httpCode, $statusCode, $status,$additional = null)
    {
        Log::error($e);
        //TODO: add exception lang file
        __('exceptions.' . $message) == 'exceptions.'.$message ?: $message = __('exceptions.' . $message);
        return $this->customResponse($message. ' '.$additional, $status, $httpCode, $statusCode);
    }

    private function getHttpCode($defaultHttpCode, $currentHttpCode)
    {
        $httpCode = $defaultHttpCode;
        if (isset($currentHttpCode) && $currentHttpCode != null) {
            $httpCode = $currentHttpCode;
        }
        return $httpCode;
    }
}
