"""Run this model in Python

> pip install azure-ai-inference
"""
import os
from azure.ai.inference import ChatCompletionsClient
from azure.ai.inference.models import AssistantMessage, SystemMessage, UserMessage
from azure.ai.inference.models import ImageContentItem, ImageUrl, TextContentItem
from azure.core.credentials import AzureKeyCredential

# To authenticate with the model you will need to generate a personal access token (PAT) in your GitHub settings.
# Create your PAT token by following instructions here: https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens
client = ChatCompletionsClient(
    endpoint = "https://models.inference.ai.azure.com",
    credential = AzureKeyCredential(os.environ["GITHUB_TOKEN"]),
)

response = client.complete(
    messages = [
        UserMessage(content = [
            TextContentItem(text = "Explain to me the Fourier equation in simple ter@/exavatar.php en veut ajouter une option pour l'itulisateur peut charger sa photo de profil et l'affiche ou place l'image de l'avatar par defautms"),
        ]),
    ],
    model = "DeepSeek-R1",
    max_tokens = 80000,
)

print(response.choices[0].message.content)