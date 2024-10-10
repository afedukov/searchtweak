# Search Models

- [Concept](#concept)
- [Components of a Search Model](#components-of-a-search-model)
- [Example](#example)
- [Managing Search Models](#managing-search-models)
- [Test Search Model](#test-search-model)
- [Model Details](#model-details)

<a name="concept"></a>
## Concept

A **Search Model** in Search Tweak represents a specific configuration that defines how search requests are processed and evaluated. Each model is built on top of a search endpoint and includes query parameters and advanced settings tailored to optimize search performance and relevancy.

<a name="components-of-a-search-model"></a>
## Components of a Search Model

- **Name**
  - The name of the search model. This should be a descriptive title that clearly indicates the purpose or focus of the model.

- **Description** (Optional)
  - A detailed description of what the search model does, including its intended use cases and any specific features or configurations.

- **Endpoint**
  - The search endpoint associated with the model. This defines the URL, method, and other parameters for making search requests.

- **Query Parameters or Request Body**
  - **Query Parameters:** Please provide the query parameters you would like to send with the request. The query variable is represented by the `#query#` string. Any instance of this pattern will be substituted with the full query. Each parameter should be on a new line in the format `key: value`.
  - **Request Body and Type:** Please provide the request body you would like to send with the request. The query variable is represented by the `#query#` string. Any instance of this pattern will be substituted with the full query.

- **Test Model** (Optional)
  - Test the model by sending a request to the endpoint and attempting to retrieve documents using the corresponding endpoint mapper code. Temporarily replace `#query#` in the Query Parameters or Request Body with any search keyword that will return at least one document.

- **Keywords** (Optional)
  - An optional list of keywords, one per line. These keywords will serve as the default set for every search evaluation created under this search model, and they can be modified later as needed.

- **Custom Headers** (Optional)
  - Please provide the custom headers you would like to send with the request. Provided headers will override the headers set in the endpoint configuration. Each header should be on a new line in the format `header: value`.

- **Advanced Settings** (Optional)
  - **Assigned Tags:** Tags can be created (a tag consists of a color and some label text). Tags can be chosen from the list of team tags and assigned to the model. This means that this tag setup will be used as the default (but can still be changed) for every Search Evaluation created under that Search Model.

<a name="example"></a>
## Example

Here is an example of a search model configuration:

#### Name

Product Search Model

#### Description

This model is designed to handle product searches, optimizing for relevancy and performance. It includes custom filters for category, price range, and availability.

#### Endpoint

Product Search

#### Query Parameters

```yaml
query: #query#
category: electronics
price_range: 100-500
availability: in_stock
```

#### or Request Body

```yaml
{
  "query": #query#,
  "filters": {
    "category": "electronics",
    "price_range": "100-500",
    "availability": "in_stock"
  }
}
```

#### Request Body Type

JSON

#### Test Model
To test this model, replace `#query#` in the Query Parameters or Request Body with a search keyword like `laptop` to ensure that it retrieves at least one document.

#### Custom Headers

```yaml
Authorization: Bearer your-api-token
Content-Type: application/json
```

#### Advanced Settings
Tags can be created and assigned to the model.

<a name="managing-search-models"></a>
## Managing Search Models

<img src="/images/docs/models.png" alt="Search Models Interface" style="width: 100%; max-width: 1436px; height: auto;">

The Search Models interface provides the following functionalities:

- **List Models**: View all existing search models.
- **Create Model**: Add a new search model with the required configuration details.
- **Edit Model**: Modify the details of an existing search model.
- **Clone Model**: Duplicate an existing search model to create a new one with similar settings.
- **Delete Model**: Remove a search model that is no longer needed.
- **Filter by Tag**: Quickly filter models based on their assigned tags.
- **Create Search Evaluation**: Initiate a new search evaluation for a specific search model.

For more details and to manage your search models, visit the [Search Models](/models) in the application.

These features ensure that you have full control over your search models, making it easy to maintain and optimize your search configurations.

<a name="test-search-model"></a>
## Test Search Model

The **Test Search Model** feature allows you to validate the configuration of your search model by sending a request to the associated endpoint and attempting to retrieve documents using the corresponding endpoint mapper code. This helps ensure that your search model is correctly set up and returns the expected results.

To use this feature, follow these steps:

1. **Prepare the Query**: Temporarily replace `#query#` in the Query Parameters or Request Body with any search keyword that will return at least one document.
2. **Send the Request**: Click the "Test" button to send a request to the endpoint.
3. **View Response**: Click the "View Response" link to see the response details.
4. **View the Results**: The results will be displayed, showing whether the request was successful and the documents retrieved.

Here is an example of a successful test:

<img src="/images/docs/test-model.png" alt="Test Search Model" style="width: 100%; max-width: 596px; height: auto;">

In this example, the search model successfully retrieved two documents. Each document includes details such as position, id, price, brand, and URL, as extracted by the endpoint mapper code.

The **Test Search Model** feature provides an easy way to verify and refine your search model configuration before deploying it for actual use.

<a name="model-details"></a>
## Model Details

The **Model Details** page provides a comprehensive view of each search model's performance and progress over time. On this page, you will find:

- **List of All Model Evaluations**: A complete list of all evaluations conducted for the model, allowing you to track and review each assessment.
- **Model Graph**: This graph includes the metrics values from every finished evaluation, enabling you to observe the progress and performance of your search model over time.

These tools help you to continuously monitor and improve your search models, ensuring they remain effective and relevant.

<img src="/images/docs/model.png" alt="Model Details Interface" style="width: 100%; max-width: 1422px; height: auto;">

---

Feel free to explore other sections of the documentation to get a better understanding of how to set up and use Search Tweak effectively.
