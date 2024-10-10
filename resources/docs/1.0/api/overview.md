# Overview

- [API Token](#api-token)
- [Authentication](#authentication)
- [Base URL](#base-url)
- [Endpoints](#endpoints)

Welcome to the Search Tweak API documentation. This guide will help you understand and utilize the various API endpoints available to interact with the Search Tweak platform.

<a name="api-token"></a>
## API Token

To access the Search Tweak API, you need an API Token. This token can be generated from the `Current Team` page on the Search Tweak website.

1. Navigate to the [Current Team](https://searchtweak.com/teams/current) page.
2. Click the `API` button.
  
  <img src="/images/docs/api/api-button.png" alt="API" style="width: 100%; max-width: 981px; height: auto;">

3. In the dialog that opens, you can:
    - See if there is an existing API token, including its creation and last usage date.
    - Delete the existing API token.
    - Generate a new API token.

**Note:** The API token is visible and can be copied only immediately after it is generated. If you lose the token, you must generate a new one.

<img src="/images/docs/api/api-token-copy.png" alt="API Token" style="width: 100%; max-width: 687px; height: auto;">

<img src="/images/docs/api/api-token.png" alt="API Token" style="width: 100%; max-width: 684px; height: auto;">

<a name="authentication"></a>
## Authentication

To authenticate your requests, you need to include the API token in the `Authorization` header of your request. The token should be prefixed with `Bearer`.

```yaml
Authorization: Bearer YOUR_API_TOKEN
```

<a name="base-url"></a>
## Base URL

The base URL for accessing the API is:

```plaintext
https://searchtweak.com/api/v1
```

<a name="endpoints"></a>
## Endpoints

- **Models** `GET /models`
    - Retrieve a list of all search models for the authenticated team.
    - For detailed documentation, visit [Models](/{{route}}/{{version}}/api/list-models).

- **Model Details** `GET /models/{id}`
    - Retrieve details of a specific model by its ID.
    - For detailed documentation, visit [Model Details](/{{route}}/{{version}}/api/get-model-details).

- **Evaluations** `GET /evaluations`
    - Retrieve a list of all evaluations for the authenticated team. 
    - For detailed documentation, visit [Evaluations](/{{route}}/{{version}}/api/list-evaluations).

- **Evaluation Details** `GET /evaluations/{id}`
    - Retrieve details of a specific evaluation by its ID.
    - For detailed documentation, visit [Evaluation Details](/{{route}}/{{version}}/api/get-evaluation-details).

- **Judgements** `GET /evaluations/{id}/judgements`
    - Retrieve the judgements for a specific evaluation.
    - For detailed documentation, visit [Judgements](/{{route}}/{{version}}/api/get-evaluation-judgements).

- **Create Evaluation** `POST /evaluations`
    - Create a new evaluation.
    - For detailed documentation, visit [Create Evaluation](/{{route}}/{{version}}/api/create-evaluation).

- **Start Evaluation** `POST /evaluations/{id}/start`
    - Start the evaluation with the given ID.
    - For detailed documentation, visit [Start Evaluation](/{{route}}/{{version}}/api/start-evaluation).

- **Stop Evaluation** `POST /evaluations/{id}/stop`
    - Stops the evaluation with the given ID.
    - For detailed documentation, visit [Stop Evaluation](/{{route}}/{{version}}/api/stop-evaluation).

- **Finish Evaluation** `POST /evaluations/{id}/finish`
    - Finish the evaluation with the given ID.
    - For detailed documentation, visit [Finish Evaluation](/{{route}}/{{version}}/api/finish-evaluation).

- **Delete Evaluation** `DELETE /evaluations/{id}`
    - Delete the evaluation with the given ID.
    - For detailed documentation, visit [Delete Evaluation](/{{route}}/{{version}}/api/delete-evaluation).
