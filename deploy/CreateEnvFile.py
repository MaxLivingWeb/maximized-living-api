import json
import boto3

with open('/var/www/html/env.txt', 'r') as file:
    deploymentGroup= file.read().rstrip()

def getSSMRequest(nextToken):
    client= boto3.client('ssm',
        region_name='us-east-2'
    )

    if nextToken:
        response= client.get_parameters_by_path(
            Path= '/{}/.env'.format(deploymentGroup),
            Recursive= True,
            WithDecryption= True,
            NextToken= nextToken
        )
    else:
        response= client.get_parameters_by_path(
                Path= '/{}/.env'.format(deploymentGroup),
                Recursive= True,
                WithDecryption= True
        )

    f= open('/var/www/html/.env','a+')
    for i in response["Parameters"]:
        name = i["Name"]
        splitName = name.rsplit('/',1)

        f.write(splitName[1])
        f.write('="')
        f.write(i['Value'])
        f.write('"\n')

    f.close()

    if "NextToken" in response:
        getSSMRequest(response["NextToken"])

    return


getSSMRequest("")
