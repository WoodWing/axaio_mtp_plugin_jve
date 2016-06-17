/**
 * MetaData.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class MetaData  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData basicMetaData;

    private com.woodwing.enterprise.interfaces.services.wfl.RightsMetaData rightsMetaData;

    private com.woodwing.enterprise.interfaces.services.wfl.SourceMetaData sourceMetaData;

    private com.woodwing.enterprise.interfaces.services.wfl.ContentMetaData contentMetaData;

    private com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData workflowMetaData;

    private com.woodwing.enterprise.interfaces.services.wfl.ExtraMetaData[] extraMetaData;

    public MetaData() {
    }

    public MetaData(
           com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData basicMetaData,
           com.woodwing.enterprise.interfaces.services.wfl.RightsMetaData rightsMetaData,
           com.woodwing.enterprise.interfaces.services.wfl.SourceMetaData sourceMetaData,
           com.woodwing.enterprise.interfaces.services.wfl.ContentMetaData contentMetaData,
           com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData workflowMetaData,
           com.woodwing.enterprise.interfaces.services.wfl.ExtraMetaData[] extraMetaData) {
           this.basicMetaData = basicMetaData;
           this.rightsMetaData = rightsMetaData;
           this.sourceMetaData = sourceMetaData;
           this.contentMetaData = contentMetaData;
           this.workflowMetaData = workflowMetaData;
           this.extraMetaData = extraMetaData;
    }


    /**
     * Gets the basicMetaData value for this MetaData.
     * 
     * @return basicMetaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData getBasicMetaData() {
        return basicMetaData;
    }


    /**
     * Sets the basicMetaData value for this MetaData.
     * 
     * @param basicMetaData
     */
    public void setBasicMetaData(com.woodwing.enterprise.interfaces.services.wfl.BasicMetaData basicMetaData) {
        this.basicMetaData = basicMetaData;
    }


    /**
     * Gets the rightsMetaData value for this MetaData.
     * 
     * @return rightsMetaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.RightsMetaData getRightsMetaData() {
        return rightsMetaData;
    }


    /**
     * Sets the rightsMetaData value for this MetaData.
     * 
     * @param rightsMetaData
     */
    public void setRightsMetaData(com.woodwing.enterprise.interfaces.services.wfl.RightsMetaData rightsMetaData) {
        this.rightsMetaData = rightsMetaData;
    }


    /**
     * Gets the sourceMetaData value for this MetaData.
     * 
     * @return sourceMetaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.SourceMetaData getSourceMetaData() {
        return sourceMetaData;
    }


    /**
     * Sets the sourceMetaData value for this MetaData.
     * 
     * @param sourceMetaData
     */
    public void setSourceMetaData(com.woodwing.enterprise.interfaces.services.wfl.SourceMetaData sourceMetaData) {
        this.sourceMetaData = sourceMetaData;
    }


    /**
     * Gets the contentMetaData value for this MetaData.
     * 
     * @return contentMetaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ContentMetaData getContentMetaData() {
        return contentMetaData;
    }


    /**
     * Sets the contentMetaData value for this MetaData.
     * 
     * @param contentMetaData
     */
    public void setContentMetaData(com.woodwing.enterprise.interfaces.services.wfl.ContentMetaData contentMetaData) {
        this.contentMetaData = contentMetaData;
    }


    /**
     * Gets the workflowMetaData value for this MetaData.
     * 
     * @return workflowMetaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData getWorkflowMetaData() {
        return workflowMetaData;
    }


    /**
     * Sets the workflowMetaData value for this MetaData.
     * 
     * @param workflowMetaData
     */
    public void setWorkflowMetaData(com.woodwing.enterprise.interfaces.services.wfl.WorkflowMetaData workflowMetaData) {
        this.workflowMetaData = workflowMetaData;
    }


    /**
     * Gets the extraMetaData value for this MetaData.
     * 
     * @return extraMetaData
     */
    public com.woodwing.enterprise.interfaces.services.wfl.ExtraMetaData[] getExtraMetaData() {
        return extraMetaData;
    }


    /**
     * Sets the extraMetaData value for this MetaData.
     * 
     * @param extraMetaData
     */
    public void setExtraMetaData(com.woodwing.enterprise.interfaces.services.wfl.ExtraMetaData[] extraMetaData) {
        this.extraMetaData = extraMetaData;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof MetaData)) return false;
        MetaData other = (MetaData) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.basicMetaData==null && other.getBasicMetaData()==null) || 
             (this.basicMetaData!=null &&
              this.basicMetaData.equals(other.getBasicMetaData()))) &&
            ((this.rightsMetaData==null && other.getRightsMetaData()==null) || 
             (this.rightsMetaData!=null &&
              this.rightsMetaData.equals(other.getRightsMetaData()))) &&
            ((this.sourceMetaData==null && other.getSourceMetaData()==null) || 
             (this.sourceMetaData!=null &&
              this.sourceMetaData.equals(other.getSourceMetaData()))) &&
            ((this.contentMetaData==null && other.getContentMetaData()==null) || 
             (this.contentMetaData!=null &&
              this.contentMetaData.equals(other.getContentMetaData()))) &&
            ((this.workflowMetaData==null && other.getWorkflowMetaData()==null) || 
             (this.workflowMetaData!=null &&
              this.workflowMetaData.equals(other.getWorkflowMetaData()))) &&
            ((this.extraMetaData==null && other.getExtraMetaData()==null) || 
             (this.extraMetaData!=null &&
              java.util.Arrays.equals(this.extraMetaData, other.getExtraMetaData())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getBasicMetaData() != null) {
            _hashCode += getBasicMetaData().hashCode();
        }
        if (getRightsMetaData() != null) {
            _hashCode += getRightsMetaData().hashCode();
        }
        if (getSourceMetaData() != null) {
            _hashCode += getSourceMetaData().hashCode();
        }
        if (getContentMetaData() != null) {
            _hashCode += getContentMetaData().hashCode();
        }
        if (getWorkflowMetaData() != null) {
            _hashCode += getWorkflowMetaData().hashCode();
        }
        if (getExtraMetaData() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getExtraMetaData());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getExtraMetaData(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(MetaData.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "MetaData"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("basicMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "BasicMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "BasicMetaData"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("rightsMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "RightsMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "RightsMetaData"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("sourceMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "SourceMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "SourceMetaData"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("contentMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ContentMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ContentMetaData"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("workflowMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "WorkflowMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "WorkflowMetaData"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("extraMetaData");
        elemField.setXmlName(new javax.xml.namespace.QName("", "ExtraMetaData"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "ExtraMetaData"));
        elemField.setNillable(true);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ExtraMetaData"));
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
